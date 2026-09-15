<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Exceptions\BusinessException;
use App\Models\Order;
use App\Support\ErrorCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 订单管理服务（docs/04 §五 API-ADM-102）
 *
 * GET 列表：分页 + 筛选（order_no / keyword 用户昵称或手机号 / order_type / status / 日期区间）。
 * 退款：仅 status=已支付(1) 可退，事务内置为 已退款(3) 并把 reason 追加到 remark。
 *   ⚠️ 真实微信退款调用不在本次范围（商户号未接入），资金原路退回待微信支付接入后执行。
 * 状态值一律用 App\Enums\OrderStatus，禁止魔法数字。
 */
class OrderService
{
    /** 列出订单（分页） */
    public function list(array $filters): LengthAwarePaginator
    {
        $page = (int) ($filters['page'] ?? 1);
        $pageSize = max(1, min(100, (int) ($filters['page_size'] ?? 20)));

        $query = Order::query()
            ->leftJoin('user_accounts', 'user_accounts.id', '=', 'order_orders.user_id')
            ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'user_accounts.id')
            ->select(
                'order_orders.*',
                'user_accounts.mobile as u_phone',
                'user_profiles.nickname as u_nickname'
            );

        if (isset($filters['order_no']) && $filters['order_no'] !== '') {
            $query->where('order_orders.order_no', 'like', '%'.$filters['order_no'].'%');
        }
        if (isset($filters['keyword']) && $filters['keyword'] !== '') {
            $kw = $filters['keyword'];
            $query->where(function ($q) use ($kw) {
                $q->where('user_profiles.nickname', 'like', '%'.$kw.'%')
                    ->orWhere('user_accounts.mobile', 'like', '%'.$kw.'%');
            });
        }
        if (isset($filters['order_type']) && $filters['order_type'] !== '' && is_numeric($filters['order_type'])) {
            $query->where('order_orders.order_type', (int) $filters['order_type']);
        }
        if (isset($filters['status']) && $filters['status'] !== '' && is_numeric($filters['status'])) {
            $query->where('order_orders.status', (int) $filters['status']);
        }
        if (isset($filters['date_from']) && $filters['date_from'] !== '') {
            $query->whereDate('order_orders.created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to']) && $filters['date_to'] !== '') {
            $query->whereDate('order_orders.created_at', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('order_orders.id')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    /** 退款（事务） */
    public function refund(int $id, string $reason, int $adminId): array
    {
        return DB::transaction(function () use ($id, $reason, $adminId) {
            /** @var Order|null $order */
            $order = Order::lockForUpdate()->find($id);
            if ($order === null) {
                throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '订单不存在', null, 404);
            }
            if ((int) $order->status !== OrderStatus::PAID->value) {
                throw new BusinessException(ErrorCode::OPERATION_FORBIDDEN, '仅已支付订单可退款', null, 409);
            }

            $oldRemark = trim((string) ($order->remark ?? ''));
            $append = '退款原因：'.$reason;
            $newRemark = $oldRemark === '' ? $append : $oldRemark."\n".$append;

            $order->update([
                'status'      => OrderStatus::REFUNDED->value,
                'remark'      => $newRemark,
                'refunded_at' => now(),
            ]);

            // 真实微信退款调用不在本次范围（商户号未接入），资金原路退回待微信支付接入后执行

            return $this->toRow($order->fresh());
        });
    }

    /** 对外行结构（时间统一 Y-m-d H:i:s 字符串，枚举文本由前端 constants 完成） */
    public function toRow(Order $order): array
    {
        return [
            'id'             => $order->id,
            'order_no'       => $order->order_no,
            'user'           => [
                'id'       => (int) $order->user_id,
                'nickname' => $order->u_nickname ?? '',
                'phone'    => $order->u_phone ?? '',
            ],
            'order_type'     => (int) $order->order_type,
            'biz_id'         => (int) $order->biz_id,
            'biz_title'      => $order->biz_title,
            'origin_amount'  => $order->origin_amount,
            'discount_amount'=> $order->discount_amount,
            'pay_amount'     => $order->pay_amount,
            'pay_channel'    => (int) $order->pay_channel,
            'status'         => (int) $order->status,
            'client_platform'=> $order->client_platform,
            'remark'         => $order->remark,
            'created_at'     => $order->created_at?->format('Y-m-d H:i:s'),
            'paid_at'        => $order->paid_at?->format('Y-m-d H:i:s'),
        ];
    }
}
