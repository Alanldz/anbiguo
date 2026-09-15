<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：user_favorite_questions ｜ 用户收藏题表
 * 登记：docs/03A-数据字典.md §1.6
 */
class UserFavoriteQuestion extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名 */
    protected $table = 'user_favorite_questions';

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
        'question_id',
        'bank_id',
        'folder_name',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [];
    }

    /** 所属用户 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** 关联题目 */
    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionItem::class, 'question_id');
    }
}
