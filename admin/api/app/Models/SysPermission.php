<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：sys_permissions ｜ 权限点表
 */
class SysPermission extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'sys_permissions';

    /** 类型：1=菜单 2=按钮 3=数据 */
    public const TYPE_MENU = 1;
    public const TYPE_BUTTON = 2;
    public const TYPE_DATA = 3;

    /** 状态：1=正常 2=停用 */
    public const STATUS_NORMAL = 1;
    public const STATUS_DISABLED = 2;

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

    protected function casts(): array
    {
        return [
            'parent_id'  => 'integer',
            'type'       => 'integer',
            'sort_order' => 'integer',
            'status'     => 'integer',
        ];
    }

    /** 子权限点（按 parent_id） */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'id');
    }
}
