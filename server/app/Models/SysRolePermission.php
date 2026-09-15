<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：sys_role_permissions ｜ 角色-权限关联表
 * 登记：docs/03A-数据字典.md §8.4
 */
class SysRolePermission extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名 */
    protected $table = 'sys_role_permissions';

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
        'role_id',
        'permission_id',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [];
    }

    /** 所属角色 */
    public function role(): BelongsTo
    {
        return $this->belongsTo(SysRole::class, 'role_id');
    }

    /** 关联权限点 */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(SysPermission::class, 'permission_id');
    }
}
