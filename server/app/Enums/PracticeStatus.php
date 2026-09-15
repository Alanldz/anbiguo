<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：练习状态 ｜ 对应字段：user_practice_records.status ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum PracticeStatus: int
{
    /** 进行中 */
    case ONGOING = 1;

    /** 已完成 */
    case COMPLETED = 2;

    /** 已放弃 */
    case ABANDONED = 3;

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::ONGOING => '进行中',
            self::COMPLETED => '已完成',
            self::ABANDONED => '已放弃',
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
