<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：bank_question_banks ｜ 题库主表（docs/03 §2.4）
 */
class QuestionBank extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'bank_question_banks';

    /** 状态：1=正常 2=隐藏 3=待审核 4=已拒绝 */
    public const STATUS_NORMAL = 1;
    public const STATUS_HIDDEN = 2;
    public const STATUS_PENDING_AUDIT = 3;
    public const STATUS_REJECTED = 4;

    /** 上架/隐藏：1=上架(正常) 2=隐藏 */
    public const VISIBLE_ON = 1;
    public const VISIBLE_OFF = 2;

    /** 审核结果：1=通过(正常) 4=拒绝 */
    public const AUDIT_PASS = 1;
    public const AUDIT_REJECT = 4;

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

    protected function casts(): array
    {
        return [
            'user_id'       => 'integer',
            'category_id'   => 'integer',
            'source_type'   => 'integer',
            'charge_type'   => 'integer',
            'price_amount'  => 'decimal:2',
            'question_count'=> 'integer',
            'chapter_count' => 'integer',
            'practice_count'=> 'integer',
            'user_count'    => 'integer',
            'is_top'        => 'integer',
            'is_recommend'  => 'integer',
            'sort_order'    => 'integer',
            'status'        => 'integer',
            'audited_by'    => 'integer',
            'audited_at'    => 'datetime',
            'last_practice_at' => 'datetime',
        ];
    }

    /** 所属用户（user_id=0 为官方题库） */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
