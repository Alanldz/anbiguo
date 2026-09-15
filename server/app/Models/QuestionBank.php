<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：bank_question_banks ｜ 题库主表（user_id=0 表示官方题库）
 * 登记：docs/03A-数据字典.md §2.2
 */
class QuestionBank extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名（模型名 QuestionBank 会推断为 question_banks，真实表为 bank_question_banks） */
    protected $table = 'bank_question_banks';

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
        'category_id',
        'title',
        'subtitle',
        'cover',
        'source_type',
        'charge_type',
        'price_amount',
        'question_count',
        'chapter_count',
        'practice_count',
        'user_count',
        'tags_json',
        'is_top',
        'is_recommend',
        'sort_order',
        'status',
        'audit_remark',
        'audited_by',
        'audited_at',
        'last_practice_at',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'price_amount' => 'decimal:2',
            'tags_json' => 'array',
            'is_top' => 'boolean',
            'is_recommend' => 'boolean',
            'audited_at' => 'datetime',
            'last_practice_at' => 'datetime',
            'question_count' => 'integer',
            'chapter_count' => 'integer',
            'practice_count' => 'integer',
            'user_count' => 'integer',
        ];
    }

    /** 归属用户 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** 所属分类 */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BankCategory::class, 'category_id');
    }

    /** 章节列表 */
    public function chapters(): HasMany
    {
        return $this->hasMany(BankChapter::class, 'bank_id');
    }

    /** 题目列表 */
    public function questions(): HasMany
    {
        return $this->hasMany(QuestionItem::class, 'bank_id');
    }

    /** 关联文件资源 */
    public function fileAssets(): HasMany
    {
        return $this->hasMany(FileAsset::class, 'bank_id');
    }

    /** 审核人（后台管理员） */
    public function auditor(): BelongsTo
    {
        return $this->belongsTo(SysAdmin::class, 'audited_by');
    }

    /** 正常状态（status=1） */
    public function scopeNormal($query)
    {
        return $query->where('status', 1);
    }

    /** 官方题库（user_id=0） */
    public function scopeOfficial($query)
    {
        return $query->where('user_id', 0);
    }

    /** 指定归属用户的题库 */
    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
