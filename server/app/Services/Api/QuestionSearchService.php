<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Enums\BankStatus;
use App\Exceptions\BusinessException;
use App\Models\QuestionBank;
use App\Models\QuestionItem;
use App\Models\QuestionOption;
use App\Models\UserFavoriteQuestion;
use App\Models\UserQuestionNote;
use App\Support\ErrorCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 题库内试题搜索服务（本期：关键词模糊搜索，不依赖 AI 大模型）
 * 台账：docs/04-API接口规范与登记表.md §三 API-SRC-001
 */
class QuestionSearchService
{
    /**
     * 题库内关键词搜索试题
     *
     * @param  array{keyword?:string, bank_id?:int, type?:int}  $params
     */
    public function search(int $userId, array $params, int $page, int $pageSize): LengthAwarePaginator
    {
        $keyword = trim((string) ($params['keyword'] ?? ''));
        if ($keyword === '') {
            throw new BusinessException(ErrorCode::PARAM_MISSING, '请输入搜索关键词');
        }

        $bankId = isset($params['bank_id']) ? (int) $params['bank_id'] : 0;
        if ($bankId > 0) {
            $this->assertBankAccessible($bankId, $userId);
        }

        $query = QuestionItem::query()
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->where('stem', 'like', '%'.self::escapeLike($keyword).'%');

        if ($bankId > 0) {
            $query->where('bank_id', $bankId);
        }
        if (! empty($params['type'])) {
            $query->where('question_type', (int) $params['type']);
        }

        $query->orderByDesc('id');

        return $query->paginate($pageSize, ['*'], 'page', $page)
            ->through(fn (QuestionItem $q) => $this->toQuestionArray($q, $userId));
    }

    /** 题库可访问性：本人 / 官方 / 状态正常（状态正常即客户端可见） */
    private function assertBankAccessible(int $bankId, int $userId): void
    {
        $bank = QuestionBank::whereKey($bankId)->whereNull('deleted_at')->first();

        if ($bank === null) {
            throw new BusinessException(ErrorCode::BANK_NOT_FOUND);
        }

        $visible = (int) $bank->user_id === $userId
            || (int) $bank->user_id === 0
            || (int) $bank->status === BankStatus::NORMAL->value;

        if (! $visible) {
            throw new BusinessException(ErrorCode::BANK_NO_PERMISSION, '无权搜索该题库');
        }
    }

    /** LIKE 通配符转义，防止 %/_ 破坏匹配 */
    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /** 组装 Question 输出（复用 API-QUE-001 形状，含 answer/analysis，以契约为准） */
    private function toQuestionArray(QuestionItem $q, int $userId): array
    {
        $options = QuestionOption::where('question_id', $q->id)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('option_key')
            ->get(['option_key', 'content'])
            ->map(fn ($o) => ['key' => $o->option_key, 'content' => $o->content])
            ->all();

        $fav = UserFavoriteQuestion::where('user_id', $userId)
            ->where('question_id', $q->id)
            ->whereNull('deleted_at')
            ->exists();

        $note = UserQuestionNote::where('user_id', $userId)
            ->where('question_id', $q->id)
            ->whereNull('deleted_at')
            ->value('content');

        return [
            'id'           => $q->id,
            'bank_id'      => (int) $q->bank_id,
            'type'         => (int) $q->question_type,
            'title'        => (string) $q->stem,
            'options'      => $options,
            'answer'       => (string) $q->answer,
            'analysis'     => (string) $q->analysis,
            'is_favorited' => $fav,
            'note'         => $note ?? '',
        ];
    }
}
