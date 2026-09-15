<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：导题任务状态 ｜ 对应字段：question_import_tasks.status ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum ImportTaskStatus: int
{
    /** 待解析 */
    case PENDING = 1;

    /** 解析中 */
    case PARSING = 2;

    /** 待校对 */
    case TO_PROOFREAD = 3;

    /** 已完成 */
    case COMPLETED = 4;

    /** 失败 */
    case FAILED = 5;

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => '待解析',
            self::PARSING => '解析中',
            self::TO_PROOFREAD => '待校对',
            self::COMPLETED => '已完成',
            self::FAILED => '失败',
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
     * 是否正在解析中（仅解析中状态）
     */
    public function isRunning(): bool
    {
        return $this === self::PARSING;
    }

    /**
     * 是否已结束（已完成或失败均视为结束）
     */
    public function isDone(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED], true);
    }
}
