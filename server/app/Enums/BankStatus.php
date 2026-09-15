<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：题库状态 ｜ 对应字段：bank_question_banks.status ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum BankStatus: int
{
    /** 正常 */
    case NORMAL = 1;

    /** 隐藏 */
    case HIDDEN = 2;

    /** 待审核 */
    case PENDING = 3;

    /** 已拒绝 */
    case REJECTED = 4;

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::NORMAL => '正常',
            self::HIDDEN => '隐藏',
            self::PENDING => '待审核',
            self::REJECTED => '已拒绝',
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
     * 是否对客户端可见（仅正常状态可见）
     */
    public function isVisible(): bool
    {
        return $this === self::NORMAL;
    }
}
