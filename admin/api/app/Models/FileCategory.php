<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：file_categories ｜ 文件分类表（学习资料分类树，用户可自建）
 * 总后台仅管理 user_id=0 的系统预置分类（docs/03 §2.8）
 * 登记：docs/04 §五 API-ADM-100
 *
 * ⚠️ 本表无 level 字段，接口层对系统预置分类恒返回 level=1。
 */
class FileCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'file_categories';

    /** 状态：1=正常 2=隐藏 */
    public const STATUS_NORMAL = 1;
    public const STATUS_HIDDEN = 2;

    /** 系统预置分类固定 user_id */
    public const SYSTEM_USER_ID = 0;

    protected $fillable = [
        'user_id',
        'parent_id',
        'name',
        'code',
        'icon',
        'file_count',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'user_id'    => 'integer',
            'parent_id'  => 'integer',
            'file_count' => 'integer',
            'sort_order' => 'integer',
            'status'     => 'integer',
        ];
    }
}
