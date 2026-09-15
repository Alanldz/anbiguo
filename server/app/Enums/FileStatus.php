<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：文件资源状态 ｜ 对应字段：file_assets.status ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum FileStatus: int
{
    /** 待上传 */
    case PENDING_UPLOAD = 1;

    /** 已上传 */
    case UPLOADED = 2;

    /** 解析中 */
    case PARSING = 3;

    /** 已归档 */
    case ARCHIVED = 4;

    /** 失败 */
    case FAILED = 5;

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING_UPLOAD => '待上传',
            self::UPLOADED => '已上传',
            self::PARSING => '解析中',
            self::ARCHIVED => '已归档',
            self::FAILED => '失败',
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
}
