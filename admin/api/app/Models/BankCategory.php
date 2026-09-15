<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：bank_categories ｜ 题库分类表（全局表，无 user_id，docs/03 §2.4）
 * 登记：docs/04 §五 API-ADM-100
 */
class BankCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'bank_categories';

    /** 状态：1=正常 2=隐藏 */
    public const STATUS_NORMAL = 1;
    public const STATUS_HIDDEN = 2;

    /** 层级：1=一级 2=二级 */
    public const LEVEL_1 = 1;
    public const LEVEL_2 = 2;

    protected $fillable = [
        'parent_id',
        'name',
        'code',
        'icon',
        'level',
        'question_bank_count',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'parent_id'          => 'integer',
            'level'              => 'integer',
            'question_bank_count'=> 'integer',
            'sort_order'         => 'integer',
            'status'             => 'integer',
        ];
    }
}
