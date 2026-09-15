<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：sys_permissions ｜ 权限点表（编码格式 模块:资源:动作）
 * 登记：docs/03A-数据字典.md §8.3
 */
class SysPermission extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名 */
    protected $table = 'sys_permissions';

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
        'parent_id',
        'name',
        'code',
        'type',
        'route_path',
        'icon',
        'sort_order',
        'status',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [];
    }

    /** 拥有该权限点的角色（多对多，关联表 sys_role_permissions） */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(SysRole::class, 'sys_role_permissions', 'permission_id', 'role_id');
    }
}
