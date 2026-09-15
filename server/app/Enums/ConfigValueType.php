<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：配置值类型 ｜ 对应字段：sys_configs.value_type ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum ConfigValueType: int
{
    /** 字符串 */
    case STRING = 1;

    /** 数字 */
    case NUMBER = 2;

    /** 布尔 */
    case BOOLEAN = 3;

    /** JSON */
    case JSON = 4;

    /** 密文 */
    case SECRET = 5;

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::STRING => '字符串',
            self::NUMBER => '数字',
            self::BOOLEAN => '布尔',
            self::JSON => 'JSON',
            self::SECRET => '密文',
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
