<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 表：exam_records ｜ 考试记录表（流水表，不做软删除）
 * 登记：docs/03A-数据字典.md §4.3
 */
class ExamRecord extends Model
{
    use HasFactory;

    /** 表名 */
    protected $table = 'exam_records';

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
        'record_no',
        'user_id',
        'paper_id',
        'bank_id',
        'paper_title',
        'total_count',
        'answered_count',
        'right_count',
        'wrong_count',
        'unanswer_count',
        'total_score',
        'get_score',
        'correct_rate',
        'duration_seconds',
        'is_passed',
        'status',
        'started_at',
        'submitted_at',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'total_score' => 'decimal:2',
            'get_score' => 'decimal:2',
            'correct_rate' => 'decimal:2',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'is_passed' => 'boolean',
            'total_count' => 'integer',
            'answered_count' => 'integer',
            'right_count' => 'integer',
            'wrong_count' => 'integer',
            'unanswer_count' => 'integer',
            'duration_seconds' => 'integer',
        ];
    }

    /** 所属用户 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** 所属试卷 */
    public function paper(): BelongsTo
    {
        return $this->belongsTo(ExamPaper::class, 'paper_id');
    }

    /** 作答明细 */
    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class, 'record_id');
    }
}
