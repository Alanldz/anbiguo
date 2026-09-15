<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：订单类型 ｜ 对应字段：order_orders.order_type ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum OrderType: int
{
    /** 会员 */
    case MEMBER = 1;

    /** 题库购买 */
    case BANK = 2;

    /** 学习资料购买 */
    case STUDY_MATERIAL = 3;

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::MEMBER => '会员',
            self::BANK => '题库购买',
            self::STUDY_MATERIAL => '学习资料购买',
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
