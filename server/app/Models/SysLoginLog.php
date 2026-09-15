<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 表：sys_login_logs ｜ 管理员登录日志表（流水表，不做软删除，仅创建时间）
 * 登记：docs/03A-数据字典.md §8.7
 */
class SysLoginLog extends Model
{
    use HasFactory;

    /** 表名 */
    protected $table = 'sys_login_logs';

    /** 主键 */
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    /** 流水表无 deleted_at，且无 updated_at；仅 created_at 由应用层显式写入 */
    public $timestamps = true;
    const UPDATED_AT = null;

    /** 可写字段 */
    protected $fillable = [
        'admin_id',
        'username',
        'login_type',
        'ip',
        'user_agent',
        'status',
        'message',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [];
    }

    /** 登录管理员 */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(SysAdmin::class, 'admin_id');
    }
}
