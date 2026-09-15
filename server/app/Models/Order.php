<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：order_orders ｜ 订单表
 * 登记：docs/03A-数据字典.md §5.2
 */
class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名（模型名 Order 会推断为 orders，真实表为 order_orders） */
    protected $table = 'order_orders';

    /** 主键 */
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    /** 时间戳由应用层/模型事件显式写入 */
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /** 可写字段 */
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

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'origin_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'pay_amount' => 'decimal:2',
            'expired_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    /** 下单用户 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** 支付流水 */
    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class, 'order_id');
    }

    /** 待支付订单（status=0） */
    public function scopePending($query)
    {
        return $query->where('status', 0);
    }

    /** 已支付订单（status=1） */
    public function scopePaid($query)
    {
        return $query->where('status', 1);
    }

    /** 指定用户的订单 */
    public function scopeOfUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
