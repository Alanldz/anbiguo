<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 客户端订单服务（我的订单列表，仅本人）
 * 台账：docs/04-API接口规范与登记表.md §三 API-ORD-001
 */
class OrderService
{
    /**
     * 我的订单列表（分页，可选按状态过滤），以当前登录用户为边界
     */
    public function list(int $userId, ?int $status, int $page, int $pageSize): LengthAwarePaginator
    {
        $query = Order::where('user_id', $userId)
            ->whereNull('deleted_at')
            ->when($status !== null, fn ($q) => $q->where('status', $status))
            ->orderByDesc('id');

        return $query->paginate($pageSize, ['*'], 'page', $page)
            ->through(fn (Order $o) => $this->toItem($o));
    }

    private function toItem(Order $o): array
    {
        $status = OrderStatus::tryFrom((int) $o->status) ?? OrderStatus::PENDING_PAYMENT;
        $type = OrderType::tryFrom((int) $o->order_type) ?? OrderType::MEMBER;

        return [
            'id'              => $o->id,
            'order_no'        => $o->order_no,
            'order_type'      => (int) $o->order_type,
            'order_type_text' => $type->label(),
            'biz_id'          => (int) $o->biz_id,
            'biz_title'       => (string) $o->biz_title,
            'origin_amount'   => (float) $o->origin_amount,
            'discount_amount' => (float) $o->discount_amount,
            'pay_amount'      => (float) $o->pay_amount,
            'status'          => (int) $o->status,
            'status_text'     => $status->label(),
            'created_at'      => $o->created_at?->format('Y-m-d H:i:s'),
            'paid_at'         => $o->paid_at?->format('Y-m-d H:i:s'),
        ];
    }
}
