<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：question_import_tasks ｜ 导题解析任务表（docs/03 §2.5）
 *
 * 总后台仅做【任务监控】（API-ADM-090），不负责解析执行（Job 在主应用）。
 */
class QuestionImportTask extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'question_import_tasks';

    /** 状态：1=待解析 2=解析中 3=待校对 4=已完成 5=失败 */
    public const STATUS_PENDING = 1;
    public const STATUS_PARSING = 2;
    public const STATUS_REVIEWING = 3;
    public const STATUS_DONE = 4;
    public const STATUS_FAILED = 5;

    /** 运行中（解析中）：用于仪表盘 import_running 统计 */
    public const STATUS_RUNNING = self::STATUS_PARSING;

    protected $fillable = [
        'task_no',
        'user_id',
        'bank_id',
        'file_id',
        'origin_name',
        'file_ext',
        'import_mode',
        'total_count',
        'success_count',
        'fail_count',
        'progress',
        'status',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id'     => 'integer',
            'bank_id'     => 'integer',
            'file_id'     => 'integer',
            'import_mode' => 'integer',
            'total_count' => 'integer',
            'success_count'=> 'integer',
            'fail_count'  => 'integer',
            'progress'    => 'integer',
            'status'      => 'integer',
            'started_at'  => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
