<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：用户状态 ｜ 对应字段：user_accounts.status ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum UserStatus: int
{
    /** 正常 */
    case NORMAL = 1;

    /** 禁用 */
    case DISABLED = 2;

    /** 注销中 */
    case CANCELING = 3;

    /** 已注销 */
    case CANCELED = 4;

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::NORMAL => '正常',
            self::DISABLED => '禁用',
            self::CANCELING => '注销中',
            self::CANCELED => '已注销',
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
