<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：题目难度 ｜ 对应字段：question_items.difficulty ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum QuestionDifficulty: int
{
    /** 易 */
    case EASY = 1;

    /** 中 */
    case MEDIUM = 2;

    /** 难 */
    case HARD = 3;

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::EASY => '易',
            self::MEDIUM => '中',
            self::HARD => '难',
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
