<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：user_wrong_questions ｜ 用户错题表（移除错题用 status，保证同题不重复入本）
 * 登记：docs/03A-数据字典.md §1.5
 */
class UserWrongQuestion extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名 */
    protected $table = 'user_wrong_questions';

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
        'wrong_count',
        'right_streak',
        'last_wrong_at',
        'mastered_at',
        'source_type',
        'last_answer',
        'status',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'last_wrong_at' => 'datetime',
            'mastered_at' => 'datetime',
            'wrong_count' => 'integer',
            'right_streak' => 'integer',
        ];
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

    /** 在错题本中的记录 */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /** 指定用户的错题 */
    public function scopeOfUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
