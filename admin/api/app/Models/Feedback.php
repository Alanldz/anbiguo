<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：sys_feedbacks ｜ 意见反馈表（新增迁移 2026_09_15_100001，docs/04 §五 API-ADM-104）
 *
 * ⚠️ images_json 存储截图 file_assets id 数组 JSON，接口层解析为数组返回。
 *   handler_id 关联 sys_admins.id（处理管理员）。
 */
class Feedback extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'sys_feedbacks';

    /** 反馈类型：1=功能异常 2=体验建议 3=其他 */
    public const TYPE_BUG = 1;
    public const TYPE_SUGGEST = 2;
    public const TYPE_OTHER = 3;

    /** 处理状态：0=待处理 1=已处理 2=已忽略 */
    public const STATUS_PENDING = 0;
    public const STATUS_HANDLED = 1;
    public const STATUS_IGNORED = 2;

    protected $fillable = [
        'user_id',
        'type',
        'content',
        'images_json',
        'contact',
        'status',
        'reply',
        'handler_id',
        'handled_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id'   => 'integer',
            'type'      => 'integer',
            'status'    => 'integer',
            'handler_id'=> 'integer',
            'handled_at'=> 'datetime',
        ];
    }
}
