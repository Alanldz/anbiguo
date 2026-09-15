<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Exceptions\BusinessException;
use App\Models\Feedback;
use App\Support\ErrorCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 意见反馈服务（docs/04 §五 API-ADM-104）
 *
 * GET 列表：分页 + 筛选（status / type / keyword=content 模糊）。
 * PUT handle：置 status(1|2) / reply / handler_id=当前管理员 / handled_at=now。
 * images_json 解析为数组返回。
 */
class FeedbackService
{
    /** 列出反馈（分页） */
    public function list(array $filters): LengthAwarePaginator
    {
        $page = (int) ($filters['page'] ?? 1);
        $pageSize = max(1, min(100, (int) ($filters['page_size'] ?? 20)));

        $query = Feedback::query()
            ->leftJoin('user_accounts', 'user_accounts.id', '=', 'sys_feedbacks.user_id')
            ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'user_accounts.id')
            ->select(
                'sys_feedbacks.*',
                'user_accounts.mobile as u_phone',
                'user_profiles.nickname as u_nickname'
            );

        if (isset($filters['status']) && $filters['status'] !== '' && is_numeric($filters['status'])) {
            $query->where('sys_feedbacks.status', (int) $filters['status']);
        }
        if (isset($filters['type']) && $filters['type'] !== '' && is_numeric($filters['type'])) {
            $query->where('sys_feedbacks.type', (int) $filters['type']);
        }
        if (isset($filters['keyword']) && $filters['keyword'] !== '') {
            $query->where('sys_feedbacks.content', 'like', '%'.$filters['keyword'].'%');
        }

        return $query->orderByDesc('sys_feedbacks.id')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    /** 处理反馈 */
    public function handle(int $id, int $status, ?string $reply, int $adminId): array
    {
        /** @var Feedback|null $fb */
        $fb = Feedback::find($id);
        if ($fb === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '反馈不存在', null, 404);
        }

        $fb->update([
            'status'     => $status,
            'reply'      => $reply ?? '',
            'handler_id' => $adminId,
            'handled_at' => now(),
        ]);

        return $this->toRow($fb->fresh());
    }

    /** 对外行结构（images 解析为数组） */
    public function toRow(Feedback $fb): array
    {
        $images = [];
        if (! empty($fb->images_json)) {
            $decoded = json_decode($fb->images_json, true);
            $images = is_array($decoded) ? $decoded : [];
        }

        return [
            'id'         => $fb->id,
            'user'       => [
                'id'       => (int) $fb->user_id,
                'nickname' => $fb->u_nickname ?? '',
                'phone'    => $fb->u_phone ?? '',
            ],
            'type'       => (int) $fb->type,
            'content'    => $fb->content,
            'images'     => $images,
            'contact'    => $fb->contact,
            'status'     => (int) $fb->status,
            'reply'      => $fb->reply,
            'handler_id' => (int) $fb->handler_id,
            'handled_at' => $fb->handled_at?->format('Y-m-d H:i:s'),
            'created_at' => $fb->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
