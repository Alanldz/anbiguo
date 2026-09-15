<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：question_items ｜ 题目表（量大，列表查询禁止 SELECT *）
 * 登记：docs/03A-数据字典.md §3.1
 */
class QuestionItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名（模型名 QuestionItem 会推断为 question_items，与表名一致，仍显式声明） */
    protected $table = 'question_items';

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
        'bank_id',
        'chapter_id',
        'question_type',
        'stem',
        'stem_preview',
        'analysis',
        'answer',
        'difficulty',
        'score',
        'media_json',
        'source_type',
        'status',
        'answer_count',
        'right_count',
        'correct_rate',
        'sort_order',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'correct_rate' => 'decimal:2',
            'answer_count' => 'integer',
            'right_count' => 'integer',
        ];
    }

    /** 所属题库 */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'bank_id');
    }

    /** 所属章节 */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(BankChapter::class, 'chapter_id');
    }

    /** 选项（选择题） */
    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class, 'question_id');
    }

    /** 用户错题关联 */
    public function wrongQuestions(): HasMany
    {
        return $this->hasMany(UserWrongQuestion::class, 'question_id');
    }

    /** 用户收藏关联 */
    public function favoriteQuestions(): HasMany
    {
        return $this->hasMany(UserFavoriteQuestion::class, 'question_id');
    }

    /** 用户笔记关联 */
    public function notes(): HasMany
    {
        return $this->hasMany(UserQuestionNote::class, 'question_id');
    }

    /** 正常状态（status=1） */
    public function scopeNormal($query)
    {
        return $query->where('status', 1);
    }

    /** 指定题库下的题目 */
    public function scopeInBank($query, $bankId)
    {
        return $query->where('bank_id', $bankId);
    }
}
