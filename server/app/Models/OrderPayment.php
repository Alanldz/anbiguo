<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 表：order_payments ｜ 支付流水表（流水表，不做软删除，对账凭证）
 * 登记：docs/03A-数据字典.md §5.3
 */
class OrderPayment extends Model
{
    use HasFactory;

    /** 表名 */
    protected $table = 'order_payments';

    /** 主键 */
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    /** 流水表无 deleted_at；时间戳由应用层/模型事件显式写入 */
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /** 可写字段 */
    protected $fillable = [
        'order_id',
        'order_no',
        'user_id',
        'transaction_no',
        'pay_channel',
        'pay_amount',
        'status',
        'notify_count',
        'notify_json',
        'fail_reason',
        'paid_at',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'pay_amount' => 'decimal:2',
            'notify_count' => 'integer',
            'notify_json' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    /** 所属订单 */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
