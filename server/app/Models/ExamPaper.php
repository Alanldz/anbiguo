<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：exam_papers ｜ 试卷表
 * 登记：docs/03A-数据字典.md §4.1
 */
class ExamPaper extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名 */
    protected $table = 'exam_papers';

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
        'paper_no',
        'user_id',
        'bank_id',
        'title',
        'paper_type',
        'generate_mode',
        'question_count',
        'total_score',
        'pass_score',
        'duration_minutes',
        'config_json',
        'status',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'total_score' => 'decimal:2',
            'pass_score' => 'decimal:2',
            'question_count' => 'integer',
        ];
    }

    /** 所属用户 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** 来源题库 */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'bank_id');
    }

    /** 试卷题目关联 */
    public function paperQuestions(): HasMany
    {
        return $this->hasMany(ExamPaperQuestion::class, 'paper_id');
    }

    /** 考试记录 */
    public function examRecords(): HasMany
    {
        return $this->hasMany(ExamRecord::class, 'paper_id');
    }
}
