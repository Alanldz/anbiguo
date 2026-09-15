<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：sys_feedbacks ｜ 意见反馈表（总后台 API-ADM-104 处理队列）
 * 登记：docs/03-数据库设计规范.md §2.10、docs/04 §二 API-FBK-001 / §五 API-ADM-104
 */
class SysFeedback extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名 */
    protected $table = 'sys_feedbacks';

    /** 主键 */
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    /** 时间戳由模型显式写入 */
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /** 可写字段 */
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

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'images_json' => 'array',
            'handled_at' => 'datetime',
        ];
    }

    /** 反馈用户 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** 处理人（总后台管理员） */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(SysAdmin::class, 'handler_id');
    }
}
