<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：user_members ｜ 用户会员表（与 user_accounts 1:1）
 * 登记：docs/03A-数据字典.md §1.3
 */
class UserMember extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名 */
    protected $table = 'user_members';

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
        'user_id',
        'level',
        'status',
        'started_at',
        'expired_at',
        'source_type',
        'last_order_id',
        'ai_import_quota',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    /** 所属用户 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
