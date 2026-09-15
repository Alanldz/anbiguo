<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：题库收费类型 ｜ 对应字段：bank_question_banks.charge_type ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum BankChargeType: int
{
    /** 免费 */
    case FREE = 1;

    /** 会员免费 */
    case MEMBER_FREE = 2;

    /** 单独购买 */
    case PURCHASE = 3;

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::FREE => '免费',
            self::MEMBER_FREE => '会员免费',
            self::PURCHASE => '单独购买',
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
