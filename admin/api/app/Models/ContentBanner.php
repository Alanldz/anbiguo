<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：content_banners ｜ 运营位表（首页轮播/推荐位，docs/03 §2.9）
 */
class ContentBanner extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'content_banners';

    /** 状态：1=启用 2=停用 */
    public const STATUS_ENABLED = 1;
    public const STATUS_DISABLED = 2;

    /** 跳转类型：1=不跳转 2=题库 3=学习资料 4=外链 5=活动页 */
    public const LINK_TYPE_NONE = 1;
    public const LINK_TYPE_BANK = 2;
    public const LINK_TYPE_ASSET = 3;
    public const LINK_TYPE_EXTERNAL = 4;
    public const LINK_TYPE_ACTIVITY = 5;

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

    protected function casts(): array
    {
        return [
            'link_type'   => 'integer',
            'click_count' => 'integer',
            'sort_order'  => 'integer',
            'status'      => 'integer',
            'started_at'  => 'datetime',
            'ended_at'    => 'datetime',
        ];
    }
}
