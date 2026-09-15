<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：sys_admins ｜ 系统管理员表（仅总后台可读写，docs/03 §2.10）
 * 登记：docs/03-数据库设计规范.md
 */
class SysAdmin extends Model implements AuthenticatableContract
{
    use AuthenticatableTrait;
    use HasFactory;
    use SoftDeletes;

    /** 表名 */
    protected $table = 'sys_admins';

    /** 状态：1=正常 2=禁用 */
    public const STATUS_NORMAL = 1;
    public const STATUS_DISABLED = 2;

    /** 是否超级管理员：0=否 1=是 */
    public const IS_SUPER = 1;
    public const NOT_SUPER = 0;

    /** 可写字段 */
    protected $fillable = [
        'username',
        'password',
        'real_name',
        'mobile',
        'email',
        'avatar',
        'is_super',
        'status',
        'login_fail_count',
        'locked_until',
        'last_login_at',
        'last_login_ip',
    ];

    /** 隐藏字段（序列化时不暴露） */
    protected $hidden = [
        'password',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'password'         => 'hashed',
            'is_super'         => 'integer',
            'status'           => 'integer',
            'login_fail_count' => 'integer',
            'locked_until'     => 'datetime',
            'last_login_at'    => 'datetime',
        ];
    }

    /** 角色（多对多，中间表 sys_admin_roles） */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            SysRole::class,
            'sys_admin_roles',
            'admin_id',
            'role_id'
        )->withTimestamps();
    }

    /** 该管理员拥有的全部权限码（跨角色去重） */
    public function permissionCodes(): array
    {
        return $this->roles
            ->loadMissing('permissions')
            ->flatMap(fn (SysRole $role) => $role->permissions->pluck('code'))
            ->unique()
            ->values()
            ->all();
    }
}
