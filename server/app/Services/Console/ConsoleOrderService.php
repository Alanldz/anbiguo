<?php

declare(strict_types=1);

namespace App\Services\Console;

use App\Enums\MemberLevel;
use App\Enums\MemberStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PayChannel;
use App\Exceptions\BusinessException;
use App\Models\MemberPlan;
use App\Models\Order;
use App\Models\UserMember;
use App\Support\ErrorCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 订单与会员服务（docs/04 §四 API-CSL-ORD-*）
 *
 * 规则：仅操作当前登录用户自己的订单与会员。
 * 注：会员来源类型（user_members.source_type）无对应枚举，按固定映射输出 source_type_text。
 */
class ConsoleOrderService
{
    /** 会员来源类型文本映射（无对应枚举，按契约固定值） */
    private const MEMBER_SOURCE_TEXT = [
        1 => '购买',
        2 => '系统赠送',
        3 => '活动奖励',
    ];

    /**
     * 我的订单列表
     */
    public function paginateOrders(int $userId, array $filters, int $page, int $pageSize): LengthAwarePaginator
    {
        $query = Order::where('user_id', $userId)->whereNull('deleted_at');

        if (! empty($filters['status'])) {
            $query->where('status', (int) $filters['status']);
        }
        if (! empty($filters['order_type'])) {
            $query->where('order_type', (int) $filters['order_type']);
        }

        return $query->orderByDesc('id')
            ->paginate($pageSize, ['*'], 'page', $page)
            ->through(fn (Order $o) => $this->toOrderItem($o));
    }

    /**
     * 订单详情
     */
    public function orderDetail(int $orderId, int $userId): array
    {
        $order = Order::whereKey($orderId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if ($order === null) {
            throw new BusinessException(ErrorCode::ORDER_NOT_FOUND);
        }

        return $this->toOrderItem($order);
    }

    /**
     * 我的会员
     */
    public function member(int $userId): array
    {
        $member = UserMember::where('user_id', $userId)->whereNull('deleted_at')->first();

        if ($member === null) {
            $level = MemberLevel::NORMAL;
            $status = MemberStatus::ACTIVE;
            return [
                'level'            => $level->value,
                'level_text'       => $level->label(),
                'status'           => $status->value,
                'status_text'      => $status->label(),
                'started_at'       => null,
                'expired_at'       => null,
                'ai_import_quota'  => 0,
                'source_type'      => 0,
                'source_type_text' => '',
            ];
        }

        $level = MemberLevel::tryFrom((int) $member->level) ?? MemberLevel::NORMAL;
        $status = MemberStatus::tryFrom((int) $member->status) ?? MemberStatus::ACTIVE;

        return [
            'level'            => $level->value,
            'level_text'       => $level->label(),
            'status'           => $status->value,
            'status_text'      => $status->label(),
            'started_at'       => $member->started_at?->toDateTimeString(),
            'expired_at'       => $member->expired_at?->toDateTimeString(),
            'ai_import_quota'  => (int) $member->ai_import_quota,
            'source_type'      => (int) $member->source_type,
            'source_type_text' => self::MEMBER_SOURCE_TEXT[(int) $member->source_type] ?? '',
        ];
    }

    /**
     * 会员套餐
     */
    public function plans(): array
    {
        return MemberPlan::query()
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->orderByDesc('is_recommend')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'level', 'duration_days', 'price_amount', 'origin_amount',
                'description', 'benefits_json', 'ai_import_quota', 'is_recommend'])
            ->map(function (MemberPlan $p) {
                $level = MemberLevel::tryFrom((int) $p->level) ?? MemberLevel::NORMAL;

                return [
                    'id'               => $p->id,
                    'name'             => $p->name,
                    'level'            => $level->value,
                    'level_text'       => $level->label(),
                    'duration_days'    => (int) $p->duration_days,
                    'price_amount'     => number_format((float) $p->price_amount, 2, '.', ''),
                    'origin_amount'    => number_format((float) $p->origin_amount, 2, '.', ''),
                    'description'      => $p->description,
                    'benefits'         => is_array($p->benefits_json) ? $p->benefits_json : [],
                    'ai_import_quota'  => (int) $p->ai_import_quota,
                    'is_recommend'     => (int) $p->is_recommend,
                ];
            })
            ->all();
    }

    /** OrderItem 输出 */
    private function toOrderItem(Order $o): array
    {
        return [
            'id'               => $o->id,
            'order_no'         => $o->order_no,
            'order_type'       => (int) $o->order_type,
            'order_type_text'  => OrderType::tryFrom((int) $o->order_type)?->label() ?? '',
            'biz_title'        => $o->biz_title,
            'origin_amount'    => number_format((float) $o->origin_amount, 2, '.', ''),
            'discount_amount'  => number_format((float) $o->discount_amount, 2, '.', ''),
            'pay_amount'       => number_format((float) $o->pay_amount, 2, '.', ''),
            'pay_channel'      => (int) $o->pay_channel,
            'pay_channel_text' => PayChannel::tryFrom((int) $o->pay_channel)?->label() ?? '',
            'status'           => (int) $o->status,
            'status_text'      => OrderStatus::tryFrom((int) $o->status)?->label() ?? '',
            'paid_at'          => $o->paid_at?->toDateTimeString(),
            'created_at'       => $o->created_at?->toDateTimeString(),
        ];
    }
}
