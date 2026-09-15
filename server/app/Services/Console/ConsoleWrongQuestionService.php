<?php

declare(strict_types=1);

namespace App\Services\Console;

use App\Enums\QuestionType;
use App\Enums\WrongQuestionStatus;
use App\Exceptions\BusinessException;
use App\Models\UserWrongQuestion;
use App\Support\ErrorCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 错题服务（docs/04 §四 API-CSL-WRG-*）
 *
 * 规则：仅返回 / 操作当前登录用户自身错题本（status=1 在错题本）。
 */
class ConsoleWrongQuestionService
{
    /**
     * 错题列表
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
                'question_items.stem_preview',
                'question_items.question_type'
            );

        if (! empty($filters['bank_id'])) {
            $query->where('user_wrong_questions.bank_id', (int) $filters['bank_id']);
        }
        if (! empty($filters['keyword'])) {
            $keyword = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim((string) $filters['keyword']));
            $query->where('question_items.stem_preview', 'like', "%{$keyword}%");
        }

        return $query->orderByDesc('user_wrong_questions.last_wrong_at')
            ->orderByDesc('user_wrong_questions.id')
            ->paginate($pageSize, ['*'], 'page', $page)
            ->through(function ($wq) {
                /** @var \stdClass $wq */
                return [
                    'id'              => (int) $wq->id,
                    'question_id'     => (int) $wq->question_id,
                    'bank_id'         => (int) $wq->bank_id,
                    'bank_title'      => $wq->bank_title ?? '',
                    'question_type'   => (int) $wq->question_type,
                    'question_type_text' => QuestionType::tryFrom((int) $wq->question_type)?->label() ?? '',
                    'stem_preview'    => $wq->stem_preview ?? '',
                    'wrong_count'     => (int) $wq->wrong_count,
                    'last_wrong_at'   => $wq->last_wrong_at ? (string) $wq->last_wrong_at : null,
                    'last_answer'     => $wq->last_answer,
                    'status'          => (int) $wq->status,
                    'status_text'     => WrongQuestionStatus::tryFrom((int) $wq->status)?->label() ?? '',
                ];
            });
    }

    /**
     * 移除单条（置为已移除）
     */
    public function remove(int $id, int $userId): void
    {
        $wq = $this->assertOwned($id, $userId);
        $wq->status = WrongQuestionStatus::REMOVED->value;
        $wq->save();
    }

    /**
     * 批量移除（置为已移除）
     */
    public function batchRemove(array $ids, int $userId): void
    {
        $ids = array_map('intval', $ids);
        UserWrongQuestion::whereIn('id', $ids)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->update(['status' => WrongQuestionStatus::REMOVED->value]);
    }

    private function assertOwned(int $id, int $userId): UserWrongQuestion
    {
        $wq = UserWrongQuestion::whereKey($id)->whereNull('deleted_at')->first();

        if ($wq === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '错题记录不存在');
        }
        if ((int) $wq->user_id !== $userId) {
            throw new BusinessException(ErrorCode::FORBIDDEN, '无权操作该错题');
        }

        return $wq;
    }
}
