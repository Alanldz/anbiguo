<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 表：file_assets ｜ 文件资源表（OSS 对象登记，docs/03 §2.8）
 * 登记：docs/04 §五 API-ADM-103
 *
 * ⚠️ 删除仅软删记录（deleted_at 置位），不调 OSS 删除接口；
 *   物理清理由 file:clean-deleted 定时任务负责。
 */
class FileAsset extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'file_assets';

    /** 业务类型：1=题库源文件 2=题目图片 3=学习资料 4=课程音视频 5=头像 6=公开静态 9=临时文件 */
    public const BIZ_TYPE_BANK_SOURCE = 1;
    public const BIZ_TYPE_QUESTION_IMG = 2;
    public const BIZ_TYPE_STUDY_ASSET = 3;
    public const BIZ_TYPE_COURSE_AV = 4;
    public const BIZ_TYPE_AVATAR = 5;
    public const BIZ_TYPE_PUBLIC = 6;
    public const BIZ_TYPE_TEMP = 9;

    /** 是否公开读：0=私有 1=公开 */
    public const PUBLIC_NO = 0;
    public const PUBLIC_YES = 1;

    protected $fillable = [
        'user_id',
        'biz_type',
        'bank_id',
        'category_id',
        'question_id',
        'origin_name',
        'object_key',
        'file_ext',
        'file_size',
        'file_hash',
        'mime_type',
        'storage',
        'is_public',
        'status',
        'ref_count',
        'expired_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id'    => 'integer',
            'biz_type'   => 'integer',
            'bank_id'    => 'integer',
            'category_id'=> 'integer',
            'question_id'=> 'integer',
            'file_size'  => 'integer',
            'is_public'  => 'integer',
            'status'     => 'integer',
            'ref_count'  => 'integer',
            'expired_at' => 'datetime',
        ];
    }
}
