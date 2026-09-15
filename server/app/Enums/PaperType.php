<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：试卷类型 ｜ 对应字段：exam_papers.paper_type ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum PaperType: int
{
    /** 模拟考试 */
    case MOCK_EXAM = 1;

    /** 自测 */
    case SELF_TEST = 2;

    /** 错题卷 */
    case WRONG_PAPER = 3;

    /** 随机组卷 */
    case RANDOM = 4;

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::MOCK_EXAM => '模拟考试',
            self::SELF_TEST => '自测',
            self::WRONG_PAPER => '错题卷',
            self::RANDOM => '随机组卷',
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
