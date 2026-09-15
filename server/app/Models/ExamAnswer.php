<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 表：exam_answers ｜ 考试作答明细表（流水表，不做软删除）
 * 登记：docs/03A-数据字典.md §4.4
 */
class ExamAnswer extends Model
{
    use HasFactory;

    /** 表名 */
    protected $table = 'exam_answers';

    /** 主键 */
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    /** 流水表无 deleted_at；时间戳由应用层/模型事件显式写入 */
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /** 可写字段 */
    protected $fillable = [
        'record_id',
        'paper_id',
        'user_id',
        'question_id',
        'user_answer',
        'is_correct',
        'score',
        'duration_seconds',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'is_correct' => 'boolean',
            'duration_seconds' => 'integer',
        ];
    }

    /** 所属考试记录 */
    public function record(): BelongsTo
    {
        return $this->belongsTo(ExamRecord::class, 'record_id');
    }

    /** 关联题目 */
    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionItem::class, 'question_id');
    }
}
