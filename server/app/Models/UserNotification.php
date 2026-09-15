<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：user_notifications ｜ 用户通知表（消息通知中心）
 * 登记：docs/04-API接口规范与登记表.md §二 API-MSG-001 ~ 004
 */
class UserNotification extends Model
{
    use SoftDeletes;

    /** 表名 */
    protected $table = 'user_notifications';

    /** 主键 */
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /** 可写字段 */
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'content',
        'biz_type',
        'biz_id',
        'is_read',
        'read_at',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'type' => 'integer',
            'biz_id' => 'integer',
            'is_read' => 'integer',
            'read_at' => 'datetime',
        ];
    }

    /** 接收用户 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** 指定用户的通知 */
    public function scopeOfUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
