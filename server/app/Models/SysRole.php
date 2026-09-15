<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：sys_roles ｜ 角色表（预置 super_admin / operation / customer_service / finance）
 * 登记：docs/03A-数据字典.md §8.2
 */
class SysRole extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名 */
    protected $table = 'sys_roles';

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
        'name',
        'code',
        'description',
        'is_system',
        'sort_order',
        'status',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    /** 角色拥有的权限点（多对多，关联表 sys_role_permissions） */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(SysPermission::class, 'sys_role_permissions', 'role_id', 'permission_id');
    }

    /** 拥有该角色的管理员（多对多，关联表 sys_admin_roles） */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(SysAdmin::class, 'sys_admin_roles', 'role_id', 'admin_id');
    }
}
