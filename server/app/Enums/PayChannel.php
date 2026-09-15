<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：支付渠道 ｜ 对应字段：order_orders.pay_channel / order_payments.pay_channel ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum PayChannel: int
{
    /** 微信支付 */
    case WECHAT = 1;

    /** 支付宝 */
    case ALIPAY = 2;

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::WECHAT => '微信支付',
            self::ALIPAY => '支付宝',
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
