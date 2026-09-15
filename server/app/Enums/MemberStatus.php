<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：会员状态 ｜ 对应字段：user_members.status ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum MemberStatus: int
{
    /** 生效 */
    case ACTIVE = 1;

    /** 已过期 */
    case EXPIRED = 2;

    /** 已冻结 */
    case FROZEN = 3;

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => '生效',
            self::EXPIRED => '已过期',
            self::FROZEN => '已冻结',
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
