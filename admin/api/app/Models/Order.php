<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：order_orders ｜ 订单表（docs/03 §2.7）
 * 登记：docs/04 §五 API-ADM-102
 *
 * ⚠️ 状态值一律用 App\Enums\OrderStatus（镜像 server/app/Enums/OrderStatus），禁止魔法数字。
 */
class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'order_orders';

    /** 订单类型：1=会员 2=题库购买 3=学习资料购买 */
    public const ORDER_TYPE_MEMBER = 1;
    public const ORDER_TYPE_BANK = 2;
    public const ORDER_TYPE_ASSET = 3;

    /** 支付渠道：1=微信支付 2=支付宝 */
    public const PAY_CHANNEL_WECHAT = 1;
    public const PAY_CHANNEL_ALIPAY = 2;

    protected $fillable = [
        'order_no',
        'user_id',
        'order_type',
        'biz_id',
        'biz_title',
        'origin_amount',
        'discount_amount',
        'pay_amount',
        'pay_channel',
        'status',
        'client_platform',
        'remark',
        'expired_at',
        'paid_at',
        'cancelled_at',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id'        => 'integer',
            'order_type'     => 'integer',
            'biz_id'         => 'integer',
            'origin_amount'  => 'decimal:2',
            'discount_amount'=> 'decimal:2',
            'pay_amount'     => 'decimal:2',
            'pay_channel'    => 'integer',
            'status'         => 'integer',
            'expired_at'     => 'datetime',
            'paid_at'        => 'datetime',
            'cancelled_at'   => 'datetime',
            'refunded_at'    => 'datetime',
        ];
    }

    /**
     * 是否已支付（可退款）
     */
    public function isPaid(): bool
    {
        return (int) $this->status === OrderStatus::PAID->value;
    }
}
