<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：order_member_plans ｜ 会员套餐表（套餐变更只影响新订单，历史订单存快照）
 * 登记：docs/03A-数据字典.md §5.1
 */
class MemberPlan extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名（模型名 MemberPlan 会推断为 member_plans，真实表为 order_member_plans） */
    protected $table = 'order_member_plans';

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
        'name',
        'level',
        'duration_days',
        'price_amount',
        'origin_amount',
        'description',
        'benefits_json',
        'ai_import_quota',
        'is_recommend',
        'sort_order',
        'status',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'price_amount' => 'decimal:2',
            'origin_amount' => 'decimal:2',
            'benefits_json' => 'array',
            'is_recommend' => 'boolean',
            'ai_import_quota' => 'integer',
        ];
    }
}
