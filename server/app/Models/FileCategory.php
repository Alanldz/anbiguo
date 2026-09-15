<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：file_categories ｜ 文件分类表（user_id=0 为系统预置分类）
 * 登记：docs/03A-数据字典.md §6.1
 */
class FileCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名 */
    protected $table = 'file_categories';

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
        'user_id',
        'parent_id',
        'name',
        'code',
        'icon',
        'file_count',
        'sort_order',
        'status',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'file_count' => 'integer',
        ];
    }

    /** 归属用户 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** 分类下文件 */
    public function fileAssets(): HasMany
    {
        return $this->hasMany(FileAsset::class, 'category_id');
    }
}
