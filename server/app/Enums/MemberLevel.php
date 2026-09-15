<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：会员等级 ｜ 对应字段：user_members.level ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum MemberLevel: int
{
    /** 普通 */
    case NORMAL = 0;

    /** 月卡 */
    case MONTHLY = 1;

    /** 季卡 */
    case QUARTERLY = 2;

    /** 年卡 */
    case YEARLY = 3;

    /** 永久 */
    case PERMANENT = 4;

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::NORMAL => '普通',
            self::MONTHLY => '月卡',
            self::QUARTERLY => '季卡',
            self::YEARLY => '年卡',
            self::PERMANENT => '永久',
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
     * 是否为付费会员（等级大于普通）
     */
    public function isVip(): bool
    {
        return $this->value > 0;
    }

    /**
     * 有效天数：普通/永久返回 0（永久需在业务层单独判定）
     */
    public function durationDays(): int
    {
        return match ($this) {
            self::NORMAL => 0,
            self::MONTHLY => 30,
            self::QUARTERLY => 90,
            self::YEARLY => 365,
            self::PERMANENT => 0,
        };
    }
}
