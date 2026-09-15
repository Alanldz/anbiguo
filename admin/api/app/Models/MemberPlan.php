<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：order_member_plans ｜ 会员套餐表（docs/03 §2.7）
 * 登记：docs/04 §五 API-ADM-105
 *
 * ⚠️ benefits_json 在接口层解析为数组返回；写入时由 Service 负责 json_encode。
 */
class MemberPlan extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'order_member_plans';

    /** 状态：1=上架 2=下架 */
    public const STATUS_ON = 1;
    public const STATUS_OFF = 2;

    /** 是否推荐：0=否 1=是 */
    public const RECOMMEND_NO = 0;
    public const RECOMMEND_YES = 1;

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

    protected function casts(): array
    {
        return [
            'level'           => 'integer',
            'duration_days'   => 'integer',
            'price_amount'    => 'decimal:2',
            'origin_amount'   => 'decimal:2',
            'ai_import_quota' => 'integer',
            'is_recommend'    => 'integer',
            'sort_order'      => 'integer',
            'status'          => 'integer',
        ];
    }
}
