<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Enums\QuestionType;
use App\Enums\WrongQuestionStatus;
use App\Exceptions\BusinessException;
use App\Models\QuestionOption;
use App\Models\UserFavoriteQuestion;
use App\Models\UserQuestionNote;
use App\Models\UserWrongQuestion;
use App\Support\ErrorCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 客户端错题服务
 * 台账：docs/04-API接口规范与登记表.md §三 API-WRG-001 ~ 002
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
}
