<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：user_practice_records ｜ 用户练习记录表（支持断点续练）
 * 登记：docs/03A-数据字典.md §1.7
 */
class UserPracticeRecord extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名 */
    protected $table = 'user_practice_records';

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
        'bank_id',
        'chapter_id',
        'practice_mode',
        'total_count',
        'answered_count',
        'right_count',
        'wrong_count',
        'correct_rate',
        'duration_seconds',
        'status',
        'started_at',
        'finished_at',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'correct_rate' => 'decimal:2',
            'total_count' => 'integer',
            'answered_count' => 'integer',
            'right_count' => 'integer',
            'wrong_count' => 'integer',
            'duration_seconds' => 'integer',
        ];
    }

    /** 所属用户 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
}
