<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：sys_roles ｜ 角色表
 */
class SysRole extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'sys_roles';

    /** 状态：1=正常 2=停用 */
    public const STATUS_NORMAL = 1;
    public const STATUS_STOPPED = 2;

    /** 是否系统预置：0=否 1=是（不可删除） */
    public const IS_SYSTEM = 1;
    public const NOT_SYSTEM = 0;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_system',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_system'  => 'integer',
            'sort_order' => 'integer',
            'status'     => 'integer',
        ];
    }

    /** 权限点（多对多，中间表 sys_role_permissions） */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            SysPermission::class,
            'sys_role_permissions',
            'role_id',
            'permission_id'
        )->withTimestamps();
    }

    /** 拥有该角色的管理员（多对多，中间表 sys_admin_roles） */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(
            SysAdmin::class,
            'sys_admin_roles',
            'role_id',
            'admin_id'
        )->withTimestamps();
    }
}
