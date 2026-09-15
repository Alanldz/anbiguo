<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：bank_categories ｜ 题库分类表（首页左侧分类 / 题库市场）
 * 登记：docs/03A-数据字典.md §2.1
 */
class BankCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** 表名 */
    protected $table = 'bank_categories';

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
        'parent_id',
        'name',
        'code',
        'icon',
        'level',
        'question_bank_count',
        'sort_order',
        'status',
    ];

    /** 类型转换 */
    protected function casts(): array
    {
        return [
            'question_bank_count' => 'integer',
        ];
    }

    /** 该分类下的题库 */
    public function questionBanks(): HasMany
    {
        return $this->hasMany(QuestionBank::class, 'category_id');
    }

    /** 子分类（自引用） */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
