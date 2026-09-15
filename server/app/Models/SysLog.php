<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 表：sys_logs ｜ 操作日志表（流水表，不做软删除，仅创建时间）
 * 登记：docs/03A-数据字典.md §8.6
 */
class SysLog extends Model
{
    use HasFactory;

    /** 表名 */
    protected $table = 'sys_logs';

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
        'admin_name',
        'module',
        'action',
        'description',
        'target_type',
        'target_id',
        'before_json',
        'after_json',
        'ip',
        'user_agent',
        'request_id',
        'result',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'before_json' => 'array',
            'after_json' => 'array',
        ];
    }

    /** 操作人（后台管理员） */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(SysAdmin::class, 'admin_id');
    }
}
