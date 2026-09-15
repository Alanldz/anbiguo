<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Enums\BankStatus;
use App\Enums\QuestionType;
use App\Enums\WrongQuestionStatus;
use App\Exceptions\BusinessException;
use App\Models\QuestionBank;
use App\Models\QuestionItem;
use App\Models\QuestionOption;
use App\Models\UserFavoriteQuestion;
use App\Models\UserQuestionNote;
use App\Models\UserWrongQuestion;
use App\Support\ErrorCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 客户端错题服务
 * 台账：docs/04-API接口规范与登记表.md §三 API-WRG-001 ~ 002
 *      docs/04-API接口规范与登记表.md §二 API-MST-001 ~ 002、API-ERR-001
 *
 * 列表项复用前端 Question 形状（id=错题记录 id，question_id=题目 id），并附错题本字段。
 * 仅返回当前用户 status=1 在错题本的记录。
 */
class WrongQuestionService
{
    /**
     * 错题列表（分页）
     */
    public function paginate(int $userId, array $filters, int $page, int $pageSize): LengthAwarePaginator
    {
        $query = UserWrongQuestion::query()
            ->leftJoin('question_items', 'question_items.id', '=', 'user_wrong_questions.question_id')
            ->leftJoin('bank_question_banks', 'bank_question_banks.id', '=', 'user_wrong_questions.bank_id')
            ->where('user_wrong_questions.user_id', $userId)
            ->where('user_wrong_questions.status', WrongQuestionStatus::IN_BOOK->value)
            ->whereNull('user_wrong_questions.deleted_at')
            ->select(
                'user_wrong_questions.*',
                'bank_question_banks.title as bank_title',
                'question_items.stem',
                'question_items.question_type',
                'question_items.answer',
                'question_items.analysis'
            );

        if (! empty($filters['bank_id'])) {
            $query->where('user_wrong_questions.bank_id', (int) $filters['bank_id']);
        }
        if (! empty($filters['question_type'])) {
            $query->where('question_items.question_type', (int) $filters['question_type']);
        }
        if (! empty($filters['keyword'])) {
            $keyword = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim((string) $filters['keyword']));
            $query->where('question_items.stem', 'like', "%{$keyword}%");
        }

        return $query->orderByDesc('user_wrong_questions.last_wrong_at')
            ->orderByDesc('user_wrong_questions.id')
            ->paginate($pageSize, ['*'], 'page', $page)
            ->through(function ($wq) use ($userId) {
                /** @var \stdClass $wq */
                $questionId = (int) $wq->question_id;

                $options = QuestionOption::where('question_id', $questionId)
                    ->whereNull('deleted_at')
                    ->orderBy('sort_order')
                    ->orderBy('option_key')
                    ->get(['option_key', 'content'])
                    ->map(fn ($o) => ['key' => $o->option_key, 'content' => $o->content])
                    ->all();

                $fav = UserFavoriteQuestion::where('user_id', $userId)
                    ->where('question_id', $questionId)
                    ->whereNull('deleted_at')
                    ->exists();

                $note = UserQuestionNote::where('user_id', $userId)
                    ->where('question_id', $questionId)
                    ->whereNull('deleted_at')
                    ->value('content');

                return [
                    'id'             => (int) $wq->id,
                    'question_id'    => $questionId,
                    'bank_id'        => (int) $wq->bank_id,
                    'type'           => (int) $wq->question_type,
                    'title'          => (string) ($wq->stem ?? ''),
                    'options'        => $options,
                    'answer'         => (string) ($wq->answer ?? ''),
                    'analysis'       => (string) ($wq->analysis ?? ''),
                    'is_favorited'   => $fav,
                    'note'           => $note ?? '',
                    'wrong_count'    => (int) $wq->wrong_count,
                    'last_wrong_at'  => $wq->last_wrong_at ? (string) $wq->last_wrong_at : null,
                    'status'         => (int) $wq->status,
                    'status_text'    => WrongQuestionStatus::tryFrom((int) $wq->status)?->label() ?? '',
                ];
            });
    }

    /**
     * 移除单条（领域软删：置 status=2 已移除）
     */
    public function remove(int $id, int $userId): void
    {
        $wq = UserWrongQuestion::whereKey($id)->whereNull('deleted_at')->first();

        if ($wq === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '错题记录不存在');
        }
        if ((int) $wq->user_id !== $userId) {
            throw new BusinessException(ErrorCode::FORBIDDEN, '无权操作该错题');
        }

        $wq->status = WrongQuestionStatus::REMOVED->value;
        $wq->save();
    }

    /**
     * 我的斩题列表（分页，API-MST-001）
     *
     * 数据源 user_wrong_questions status=3（已掌握），
     * JOIN question_items / bank_question_banks，行结构与收藏列表扁平风格一致。
     */
    public function masteredPaginate(int $userId, array $filters, int $page, int $pageSize): LengthAwarePaginator
    {
        $query = UserWrongQuestion::query()
            ->leftJoin('question_items', 'question_items.id', '=', 'user_wrong_questions.question_id')
            ->leftJoin('bank_question_banks', 'bank_question_banks.id', '=', 'user_wrong_questions.bank_id')
            ->where('user_wrong_questions.user_id', $userId)
            ->where('user_wrong_questions.status', WrongQuestionStatus::MASTERED->value)
            ->whereNull('user_wrong_questions.deleted_at')
            ->select(
                'user_wrong_questions.id',
                'user_wrong_questions.question_id',
                'user_wrong_questions.bank_id',
                'user_wrong_questions.wrong_count',
                'user_wrong_questions.right_streak',
                'user_wrong_questions.mastered_at',
                'question_items.stem',
                'question_items.question_type',
                'question_items.difficulty',
                'bank_question_banks.title as bank_name'
            );

        if (! empty($filters['bank_id'])) {
            $query->where('user_wrong_questions.bank_id', (int) $filters['bank_id']);
        }
        if (! empty($filters['keyword'])) {
            // 题干模糊匹配，转义 %/_ 防拖库
            $keyword = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim((string) $filters['keyword']));
            $query->where('question_items.stem', 'like', "%{$keyword}%");
        }

        return $query->orderByDesc('user_wrong_questions.mastered_at')
            ->orderByDesc('user_wrong_questions.id')
            ->paginate($pageSize, ['*'], 'page', $page)
            ->through(function ($row) {
                /** @var \stdClass $row */
                $questionId = (int) $row->question_id;

                return [
                    'id'                  => (int) $row->id,
                    'question_id'         => $questionId,
                    'bank_id'             => (int) $row->bank_id,
                    'bank_name'           => (string) ($row->bank_name ?? ''),
                    'question_title'      => (string) ($row->stem ?? ''),
                    'question_type'       => (int) ($row->question_type ?? 0),
                    'question_options'    => $this->options($questionId),
                    'question_difficulty' => (int) ($row->difficulty ?? 0),
                    'wrong_count'         => (int) $row->wrong_count,
                    'right_streak'        => (int) $row->right_streak,
                    'mastered_at'         => $row->mastered_at ? (string) $row->mastered_at : null,
                ];
            });
    }

    /**
     * 找回已掌握题目（API-MST-002）
     *
     * 仅本人 + status=3 已掌握的记录可找回，否则按数据不存在处理；
     * 置回在错题本（status=1）并清零斩题计数与掌握时间。
     */
    public function restoreMastered(int $id, int $userId): void
    {
        $wq = UserWrongQuestion::whereKey($id)
            ->where('user_id', $userId)
            ->where('status', WrongQuestionStatus::MASTERED->value)
            ->whereNull('deleted_at')
            ->first();

        if ($wq === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '已掌握记录不存在');
        }

        $wq->status = WrongQuestionStatus::IN_BOOK->value;
        $wq->right_streak = 0;
        $wq->mastered_at = null;
        $wq->save();
    }

    /** 题目选项（与既有 QUE-001 / WRG-001 / FAV-001 取选项方式保持一致） */
    private function options(int $questionId): array
    {
        return QuestionOption::where('question_id', $questionId)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('option_key')
            ->get(['option_key', 'content'])
            ->map(fn ($o) => ['key' => $o->option_key, 'content' => $o->content])
            ->all();
    }

    /**
     * 易错题集（分页，API-ERR-001）
     *
     * 数据源 question_items 全站冗余统计（answer_count/right_count/correct_rate），
     * 按 correct_rate 升序、answer_count 降序；is_wrong 一次 in 查询判定，禁止循环查库。
     */
    public function errorPronePaginate(int $userId, int $bankId, int $page, int $pageSize): LengthAwarePaginator
    {
        $paginator = QuestionItem::query()
            ->where('bank_id', $bankId)
            ->where('status', 1) // question_items.status 1=正常（列注释口径，暂无枚举类）
            ->whereNull('deleted_at')
            ->where('answer_count', '>', 0)
            ->orderByAsc('correct_rate')
            ->orderByDesc('answer_count')
            ->paginate($pageSize, ['*'], 'page', $page);

        // 本页题目 id 集合一次 in 查询判定 is_wrong，禁止循环查库
        $questionIds = collect($paginator->items())->pluck('id')->all();
        $wrongQuestionIds = $questionIds === [] ? collect() : UserWrongQuestion::query()
            ->where('user_id', $userId)
            ->where('status', WrongQuestionStatus::IN_BOOK->value)
            ->whereNull('deleted_at')
            ->whereIn('question_id', $questionIds)
            ->pluck('question_id');

        return $paginator->through(function (QuestionItem $q) use ($wrongQuestionIds) {
            // 题干展示：stem_preview 优先，为空取 stem 截断 100 字
            $title = trim((string) $q->stem_preview);
            if ($title === '') {
                $title = mb_substr(trim((string) $q->stem), 0, 100);
            }

            return [
                'id'                  => (int) $q->id,
                'bank_id'             => (int) $q->bank_id,
                'question_title'      => $title,
                'question_type'       => (int) $q->question_type,
                'question_options'    => $this->options((int) $q->id),
                'question_difficulty' => (int) $q->difficulty,
                'correct_rate'        => (float) $q->correct_rate,
                'answer_count'        => (int) $q->answer_count,
                'is_wrong'            => $wrongQuestionIds->contains((int) $q->id),
            ];
        });
    }

    /**
     * 易错题集题库归属校验（API-ERR-001）
     *
     * 题库不存在抛 BANK_NOT_FOUND；非本人私有题库越权抛 FORBIDDEN（官方题库 user_id=0 放行）。
     */
    public function assertErrorProneBank(int $bankId, int $userId): void
    {
        $bank = QuestionBank::whereKey($bankId)->whereNull('deleted_at')->first();

        if ($bank === null) {
            throw new BusinessException(ErrorCode::BANK_NOT_FOUND, '题库不存在');
        }

        if ((int) $bank->user_id !== $userId && (int) $bank->user_id !== 0) {
            throw new BusinessException(ErrorCode::FORBIDDEN, '无权查看该题库的易错题集');
        }
    }
}
