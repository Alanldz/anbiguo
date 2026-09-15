<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：exam_paper_questions ｜ 试卷题目关联表（组卷完成后冻结，保证历史可复现）
 * 登记：docs/03A-数据字典.md §4.2
 */
class ExamPaperQuestion extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名 */
    protected $table = 'exam_paper_questions';

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
        'paper_id',
        'question_id',
        'bank_id',
        'sort_order',
        'score',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
        ];
    }

    /** 所属试卷 */
    public function paper(): BelongsTo
    {
        return $this->belongsTo(ExamPaper::class, 'paper_id');
    }

    /** 关联题目 */
    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionItem::class, 'question_id');
    }
}
