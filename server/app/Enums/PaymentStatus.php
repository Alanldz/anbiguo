<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：支付流水状态 ｜ 对应字段：order_payments.status ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum PaymentStatus: int
{
    /** 待支付 */
    case PENDING = 1;

    /** 成功 */
    case SUCCESS = 2;

    /** 失败 */
    case FAILED = 3;

    /** 已退款 */
    case REFUNDED = 4;

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => '待支付',
            self::SUCCESS => '成功',
            self::FAILED => '失败',
            self::REFUNDED => '已退款',
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
