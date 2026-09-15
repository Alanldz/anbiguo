<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：文件业务类型 ｜ 对应字段：file_assets.biz_type ｜ 登记：docs/03-数据库设计规范.md §三 ｜ 大小/后缀取 docs/05-OSS存储与文件分类规范.md §三
 */
enum FileBizType: int
{
    /** 题库源文件 */
    case BANK_SOURCE = 1;

    /** 题目图片 */
    case QUESTION_IMAGE = 2;

    /** 学习资料 */
    case STUDY_MATERIAL = 3;

    /** 课程音视频 */
    case COURSE_MEDIA = 4;

    /** 头像 */
    case AVATAR = 5;

    /** 公开静态 */
    case PUBLIC_STATIC = 6;

    /** 临时文件 */
    case TEMP = 9;

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::BANK_SOURCE => '题库源文件',
            self::QUESTION_IMAGE => '题目图片',
            self::STUDY_MATERIAL => '学习资料',
            self::COURSE_MEDIA => '课程音视频',
            self::AVATAR => '头像',
            self::PUBLIC_STATIC => '公开静态',
            self::TEMP => '临时文件',
        };
    }

    /**
     * 全部标签映射：[值 => 中文标签]，用于下拉选项
     *
     * @return array<int, string>
     */
    public static function labelMap(): array
    {
        $map = [];
        foreach (self::cases() as $case) {
            $map[$case->value] = $case->label();
        }

        return $map;
    }

    /**
     * 校验给定值是否合法（用于入参校验）
     */
    public static function isValid(int|string $value): bool
    {
        return in_array($value, array_column(self::cases(), 'value'), true);
    }

    /**
     * 是否公开可读（头像与公开静态可直接公开访问）
     */
    public function isPublicReadable(): bool
    {
        return in_array($this, [self::AVATAR, self::PUBLIC_STATIC], true);
    }

    /**
     * 大小上限（字节），按 docs/05 §三：1→50MB 2→10MB 3→200MB 4→1GB 5→5MB 6→5MB 9→50MB
     */
    public function maxSizeInBytes(): int
    {
        return match ($this) {
            self::BANK_SOURCE => 52428800,     // 50 MB
            self::QUESTION_IMAGE => 10485760,  // 10 MB
            self::STUDY_MATERIAL => 209715200, // 200 MB
            self::COURSE_MEDIA => 1073741824,  // 1 GB
            self::AVATAR => 5242880,           // 5 MB
            self::PUBLIC_STATIC => 5242880,    // 5 MB
            self::TEMP => 52428800,            // 50 MB（沿用业务上限）
        };
    }

    /**
     * 允许扩展名白名单（小写，不含点），按 docs/05 §三；9 临时文件取全部业务类型白名单并集去重
     *
     * @return array<int, string>
     */
    public function allowedExtensions(): array
    {
        return match ($this) {
            self::BANK_SOURCE => ['doc', 'docx', 'xls', 'xlsx', 'pdf', 'txt', 'csv'],
            self::QUESTION_IMAGE => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            self::STUDY_MATERIAL => ['doc', 'docx', 'pdf', 'ppt', 'pptx', 'mp4', 'mp3', 'zip'],
            self::COURSE_MEDIA => ['mp4', 'mov', 'm4a', 'mp3'],
            self::AVATAR => ['jpg', 'png', 'webp'],
            self::PUBLIC_STATIC => ['png', 'jpg', 'svg', 'json'],
            self::TEMP => [
                'csv', 'doc', 'docx', 'gif', 'jpeg', 'jpg', 'json', 'm4a', 'mov', 'mp3',
                'mp4', 'pdf', 'png', 'ppt', 'pptx', 'svg', 'txt', 'webp', 'xls', 'xlsx', 'zip',
            ],
        };
    }
}
