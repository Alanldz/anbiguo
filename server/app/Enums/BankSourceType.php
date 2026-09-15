<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：题库来源类型 ｜ 对应字段：bank_question_banks.source_type ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum BankSourceType: int
{
    /** 用户上传 */
    case USER_UPLOAD = 1;

    /** 官方 */
    case OFFICIAL = 2;

    /** 购买 */
    case PURCHASE = 3;

    /** AI生成 */
    case AI_GENERATED = 4;

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::USER_UPLOAD => '用户上传',
            self::OFFICIAL => '官方',
            self::PURCHASE => '购买',
            self::AI_GENERATED => 'AI生成',
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
