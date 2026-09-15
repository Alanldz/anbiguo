<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：错题状态 ｜ 对应字段：user_wrong_questions.status ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum WrongQuestionStatus: int
{
    /** 在错题本 */
    case IN_BOOK = 1;

    /** 已移除 */
    case REMOVED = 2;

    /** 已掌握 */
    case MASTERED = 3;

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::IN_BOOK => '在错题本',
            self::REMOVED => '已移除',
            self::MASTERED => '已掌握',
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
