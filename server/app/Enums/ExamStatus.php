<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：考试状态 ｜ 对应字段：exam_records.status ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum ExamStatus: int
{
    /** 进行中 */
    case ONGOING = 1;

    /** 已交卷 */
    case SUBMITTED = 2;

    /** 超时自动交卷 */
    case TIMEOUT = 3;

    /** 已作废 */
    case CANCELED = 4;

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::ONGOING => '进行中',
            self::SUBMITTED => '已交卷',
            self::TIMEOUT => '超时自动交卷',
            self::CANCELED => '已作废',
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
     * 是否已结束（已交卷或超时自动交卷视为结束）
     */
    public function isFinished(): bool
    {
        return in_array($this, [self::SUBMITTED, self::TIMEOUT], true);
    }
}
