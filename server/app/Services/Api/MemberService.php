<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Enums\MemberLevel;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Exceptions\BusinessException;
use App\Models\MemberPlan;
use App\Models\Order;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\DB;

/**
 * 客户端会员服务（本期：套餐列表 + 开通下单，真实支付待 PAY 接入）
 * 台账：docs/04-API接口规范与登记表.md §三 API-MBR-001 ~ 002
 */
class MemberService
{
    /**
     * 会员套餐列表（status=1 上架、未软删），按 sort_order 升序
     */
    public function plans(): array
    {
        $plans = MemberPlan::where('status', 1)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return [
            'list' => $plans->map(fn (MemberPlan $p) => $this->toPlanArray($p))->all(),
        ];
    }

    /**
     * 开通会员下单：校验套餐存在且上架 → 创建待支付订单（支付留待 API-PAY-001/002）
     */
    public function createOrder(int $userId, int $planId, string $platform): array
    {
        $plan = MemberPlan::whereKey($planId)->whereNull('deleted_at')->first();

        if ($plan === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '套餐不存在');
        }
        if ((int) $plan->status !== 1) {
            throw new BusinessException(ErrorCode::PLAN_OFF_SHELF);
        }

        $order = DB::transaction(function () use ($userId, $plan, $platform) {
            return Order::create([
                'order_no'        => $this->genOrderNo(),
                'user_id'         => $userId,
                'order_type'      => OrderType::MEMBER->value,
                'biz_id'          => $plan->id,
                'biz_title'       => (string) $plan->name,
                'origin_amount'   => $plan->price_amount,
                'discount_amount' => 0,
                'pay_amount'      => $plan->price_amount,
                'pay_channel'     => 1,
                'status'          => OrderStatus::PENDING_PAYMENT->value,
                'client_platform' => $platform,
                'expired_at'      => now()->addMinutes(30),
            ]);
        });

        return $this->toOrderArray($order);
    }

    private function toPlanArray(MemberPlan $p): array
    {
        $level = MemberLevel::tryFrom((int) $p->level) ?? MemberLevel::MONTHLY;

        return [
            'id'              => $p->id,
            'name'            => (string) $p->name,
            'level'           => (int) $p->level,
            'level_text'      => $level->label(),
            'duration_days'   => (int) $p->duration_days,
            'price_amount'    => (float) $p->price_amount,
            'origin_amount'   => (float) $p->origin_amount,
            'description'     => (string) $p->description,
            'benefits'        => $this->benefitsToArray($p->benefits_json),
            'ai_import_quota' => (int) $p->ai_import_quota,
            'is_recommend'    => (bool) $p->is_recommend,
        ];
    }

    /**
     * benefits_json 已是 array（模型 cast），兼容 [字符串] 与 [{text|title}]
     */
    private function benefitsToArray($json): array
    {
        if (! is_array($json)) {
            return [];
        }

        return array_values(array_map(
            fn ($item) => is_array($item)
                ? (string) ($item['text'] ?? $item['title'] ?? '')
                : (string) $item,
            $json
        ));
    }

    private function toOrderArray(Order $o): array
    {
        $status = OrderStatus::tryFrom((int) $o->status) ?? OrderStatus::PENDING_PAYMENT;

        return [
            'order_no'        => $o->order_no,
            'order_type'      => (int) $o->order_type,
            'biz_id'          => (int) $o->biz_id,
            'biz_title'       => (string) $o->biz_title,
            'origin_amount'   => (float) $o->origin_amount,
            'discount_amount' => (float) $o->discount_amount,
            'pay_amount'      => (float) $o->pay_amount,
            'status'          => (int) $o->status,
            'status_text'     => $status->label(),
            'expired_at'      => $o->expired_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function genOrderNo(): string
    {
        return 'OD'.date('YmdHis').str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }
}
