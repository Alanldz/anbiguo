<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：question_options ｜ 题项（选项）表
 * 登记：docs/03A-数据字典.md §3.2
 */
class QuestionOption extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名 */
    protected $table = 'question_options';

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
        'option_key',
        'content',
        'is_correct',
        'media_json',
        'sort_order',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    /** 所属题目 */
    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionItem::class, 'question_id');
    }
}
