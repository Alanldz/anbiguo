<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：sys_admin_roles ｜ 管理员-角色关联表
 * 登记：docs/03A-数据字典.md §8.5
 */
class SysAdminRole extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名 */
    protected $table = 'sys_admin_roles';

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
        'admin_id',
        'role_id',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [];
    }

    /** 所属管理员 */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(SysAdmin::class, 'admin_id');
    }

    /** 关联角色 */
    public function role(): BelongsTo
    {
        return $this->belongsTo(SysRole::class, 'role_id');
    }
}
