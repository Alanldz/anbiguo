<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：user_daily_stats ｜ 用户每日学习统计表（学习空间数据来源）
 * 登记：docs/03A-数据字典.md §1.4
 */
class UserDailyStat extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名 */
    protected $table = 'user_daily_stats';

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
        'stat_date',
        'answer_count',
        'right_count',
        'wrong_count',
        'practice_count',
        'exam_count',
        'duration_seconds',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'stat_date' => 'date',
        ];
    }

    /** 所属用户 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
