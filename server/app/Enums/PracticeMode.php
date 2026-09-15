<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：练习模式 ｜ 对应字段：user_practice_records.practice_mode ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum PracticeMode: int
{
    /** 顺序练习 */
    case SEQUENCE = 1;

    /** 随机练习 */
    case RANDOM = 2;

    /** 专项练习 */
    case CHAPTER = 3;

    /** 错题重做 */
    case WRONG_REDO = 4;

    /** 闪卡 */
    case FLASHCARD = 5;

    /** 斩题 */
    case BEHEAD = 6;

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::SEQUENCE => '顺序练习',
            self::RANDOM => '随机练习',
            self::CHAPTER => '专项练习',
            self::WRONG_REDO => '错题重做',
            self::FLASHCARD => '闪卡',
            self::BEHEAD => '斩题',
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
