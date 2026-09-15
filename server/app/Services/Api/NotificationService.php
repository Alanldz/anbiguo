<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Exceptions\BusinessException;
use App\Models\UserNotification;
use App\Support\ErrorCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 消息通知中心服务
 * 台账：docs/04-API接口规范与登记表.md §二 API-MSG-001 ~ 004
 *
 * 通知为用户私有数据，所有查询强制 user_id 边界。
 * type 语义：1=系统通知 2=互动通知 3=业务通知（列注释，暂无枚举类，沿用先例中文注释口径）。
 */
class NotificationService
{
    /**
     * 通知列表（分页，按 created_at 降序）
     */
    public function paginate(int $userId, array $filters, int $page, int $pageSize): LengthAwarePaginator
    {
        $query = UserNotification::query()
            ->where('user_id', $userId)
            ->whereNull('deleted_at');

        if (isset($filters['type']) && $filters['type'] !== '') {
            $query->where('type', (int) $filters['type']);
        }
        if (isset($filters['is_read']) && $filters['is_read'] !== '') {
            $query->where('is_read', (int) $filters['is_read']);
        }

        return $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($pageSize, ['*'], 'page', $page)
            ->through(function (UserNotification $n) {
                return [
                    'id'        => (int) $n->id,
                    'type'      => (int) $n->type,
                    'title'     => (string) $n->title,
                    'content'   => (string) $n->content,
                    'biz_type'  => (string) $n->biz_type,
                    'biz_id'    => (int) $n->biz_id,
                    'is_read'   => (int) $n->is_read,
                    'read_at'   => $n->read_at ? (string) $n->read_at : null,
                    'created_at' => $n->created_at ? (string) $n->created_at : null,
                ];
            });
    }

    /**
     * 未读通知数量
     */
    public function unreadCount(int $userId): int
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * 标记单条已读（幂等：已读再调直接返回成功）
     */
    public function markRead(int $id, int $userId): void
    {
        $notification = UserNotification::query()
            ->whereKey($id)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if ($notification === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '通知不存在');
        }

        if ((int) $notification->is_read === 0) {
            $notification->is_read = 1;
            $notification->read_at = now();
            $notification->save();
        }
    }

    /**
     * 全部已读（批量更新本人未读记录），返回标记条数
     */
    public function readAll(int $userId): int
    {
        return (int) UserNotification::query()
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->whereNull('deleted_at')
            ->update([
                'is_read'    => 1,
                'read_at'    => now(),
                'updated_at' => now(),
            ]);
    }
}
