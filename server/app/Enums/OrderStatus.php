<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：订单状态 ｜ 对应字段：order_orders.status ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum OrderStatus: int
{
    /** 待支付 */
    case PENDING_PAYMENT = 0;

    /** 已支付 */
    case PAID = 1;

    /** 已取消 */
    case CANCELED = 2;

    /** 已退款 */
    case REFUNDED = 3;

    /** 已关闭 */
    case CLOSED = 4;

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING_PAYMENT => '待支付',
            self::PAID => '已支付',
            self::CANCELED => '已取消',
            self::REFUNDED => '已退款',
            self::CLOSED => '已关闭',
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
     * 是否已支付
     */
    public function isPaid(): bool
    {
        return $this === self::PAID;
    }

    /**
     * 是否为终态（已取消/已退款/已关闭均不可再流转）
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::CANCELED, self::REFUNDED, self::CLOSED], true);
    }
}
