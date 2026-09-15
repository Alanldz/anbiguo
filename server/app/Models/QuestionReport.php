<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：question_reports ｜ 试题报错表（总后台审核队列之一）
 * 登记：docs/03A-数据字典.md §3.3
 */
class QuestionReport extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名 */
    protected $table = 'question_reports';

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
        'question_id',
        'bank_id',
        'user_id',
        'report_type',
        'content',
        'images_json',
        'status',
        'handled_by',
        'handle_remark',
        'handled_at',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'images_json' => 'array',
            'handled_at' => 'datetime',
        ];
    }

    /** 关联题目 */
    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionItem::class, 'question_id');
    }

    /** 反馈用户 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** 处理人（后台管理员） */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(SysAdmin::class, 'handled_by');
    }
}
