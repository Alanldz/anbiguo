<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：题目类型 ｜ 对应字段：question_items.question_type ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum QuestionType: int
{
    /** 单选 */
    case SINGLE_CHOICE = 1;

    /** 多选 */
    case MULTIPLE_CHOICE = 2;

    /** 判断 */
    case JUDGE = 3;

    /** 填空 */
    case FILL_BLANK = 4;

    /** 简答 */
    case SHORT_ANSWER = 5;

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::SINGLE_CHOICE => '单选',
            self::MULTIPLE_CHOICE => '多选',
            self::JUDGE => '判断',
            self::FILL_BLANK => '填空',
            self::SHORT_ANSWER => '简答',
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
     * 是否有选项（单选/多选/判断有选项，填空/简答无选项）
     */
    public function hasOptions(): bool
    {
        return match ($this) {
            self::SINGLE_CHOICE, self::MULTIPLE_CHOICE, self::JUDGE => true,
            self::FILL_BLANK, self::SHORT_ANSWER => false,
        };
    }

    /**
     * 是否允许多选答案（仅多选）
     */
    public function allowMultipleAnswer(): bool
    {
        return $this === self::MULTIPLE_CHOICE;
    }
}
