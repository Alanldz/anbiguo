<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：bank_chapters ｜ 题库章节表（专项练习、章节分析用）
 * 登记：docs/03A-数据字典.md §2.3
 */
class BankChapter extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名 */
    protected $table = 'bank_chapters';

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
        'bank_id',
        'parent_id',
        'name',
        'level',
        'question_count',
        'sort_order',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'question_count' => 'integer',
        ];
    }

    /** 所属题库 */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'bank_id');
    }

    /** 章节内题目 */
    public function questions(): HasMany
    {
        return $this->hasMany(QuestionItem::class, 'chapter_id');
    }
}
