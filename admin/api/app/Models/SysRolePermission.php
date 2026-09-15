<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：sys_role_permissions ｜ 角色-权限关联表（中间表）
 */
class SysRolePermission extends Pivot
{
    use SoftDeletes;

    protected $table = 'sys_role_permissions';

    public $incrementing = true;

    protected $fillable = [
        'role_id',
        'permission_id',
    ];
}
