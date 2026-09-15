<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：导题模式 ｜ 对应字段：question_import_tasks.import_mode ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum ImportMode: int
{
    /** 文档导入 */
    case DOC_IMPORT = 1;

    /** 手动录入 */
    case MANUAL = 2;

    /** 拍照OCR */
    case PHOTO_OCR = 3;

    /** 试题答案分离 */
    case QAS_SPLIT = 4;

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::DOC_IMPORT => '文档导入',
            self::MANUAL => '手动录入',
            self::PHOTO_OCR => '拍照OCR',
            self::QAS_SPLIT => '试题答案分离',
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
