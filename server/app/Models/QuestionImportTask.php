<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：question_import_tasks ｜ 导题解析任务表（AI 导题 / 文档解析进度载体）
 * 登记：docs/03A-数据字典.md §3.4
 */
class QuestionImportTask extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名 */
    protected $table = 'question_import_tasks';

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
        'result_json',
        'started_at',
        'finished_at',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'result_json' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'total_count' => 'integer',
            'success_count' => 'integer',
            'fail_count' => 'integer',
        ];
    }

    /** 发起用户 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** 目标题库 */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'bank_id');
    }

    /** 源文件 */
    public function file(): BelongsTo
    {
        return $this->belongsTo(FileAsset::class, 'file_id');
    }
}
