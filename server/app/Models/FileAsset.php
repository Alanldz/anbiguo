<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：file_assets ｜ 文件资源表（OSS 登记，唯一入口，不允许孤儿文件）
 * 登记：docs/03A-数据字典.md §6.2
 */
class FileAsset extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名 */
    protected $table = 'file_assets';

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
        'biz_type',
        'bank_id',
        'category_id',
        'question_id',
        'origin_name',
        'object_key',
        'file_ext',
        'file_size',
        'file_hash',
        'mime_type',
        'storage',
        'is_public',
        'status',
        'ref_count',
        'expired_at',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'expired_at' => 'datetime',
            'is_public' => 'boolean',
            'ref_count' => 'integer',
        ];
    }

    /** 归属用户 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** 归属题库 */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'bank_id');
    }

    /** 归属文件分类 */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FileCategory::class, 'category_id');
    }

    /** 已上传文件（status=2） */
    public function scopeUploaded($query)
    {
        return $query->where('status', 2);
    }

    /** 指定归属用户的文件 */
    public function scopeOfUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
