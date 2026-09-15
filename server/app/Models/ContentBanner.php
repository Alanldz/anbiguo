<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：content_banners ｜ 运营位表（首页轮播 / 推荐位 / 弹窗）
 * 登记：docs/03A-数据字典.md §7.1
 */
class ContentBanner extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名 */
    protected $table = 'content_banners';

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
        'position_code',
        'title',
        'subtitle',
        'image',
        'link_type',
        'link_value',
        'client_scope',
        'click_count',
        'sort_order',
        'status',
        'started_at',
        'ended_at',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'click_count' => 'integer',
        ];
    }

    /** 启用且在投放时间段内的运营位 */
    public function scopeEnabled($query)
    {
        return $query->where('status', 1)
            ->where(function ($q) {
                $q->whereNull('started_at')->orWhere('started_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ended_at')->orWhere('ended_at', '>=', now());
            });
    }
}
