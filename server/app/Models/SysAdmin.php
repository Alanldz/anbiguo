<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：sys_admins ｜ 系统管理员表（独立账号体系，与客户端用户不互通）
 * 登记：docs/03A-数据字典.md §8.1
 */
class SysAdmin extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名 */
    protected $table = 'sys_admins';

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

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_super' => 'boolean',
            'login_fail_count' => 'integer',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /** 拥有的角色（多对多，关联表 sys_admin_roles） */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(SysRole::class, 'sys_admin_roles', 'admin_id', 'role_id');
    }
}
