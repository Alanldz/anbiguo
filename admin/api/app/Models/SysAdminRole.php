<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：sys_admin_roles ｜ 管理员-角色关联表（中间表）
 */
class SysAdminRole extends Pivot
{
    use SoftDeletes;

    protected $table = 'sys_admin_roles';

    public $incrementing = true;

    protected $fillable = [
        'admin_id',
        'role_id',
    ];
}
