<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：Banner 投放位置 ｜ 对应字段：content_banners.position_code ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum BannerPosition: string
{
    /** 首页轮播 */
    case HOME_TOP = 'home_top';

    /** 首页推荐 */
    case HOME_RECOMMEND = 'home_recommend';

    /** 我的页入口 */
    case MINE_ENTRY = 'mine_entry';

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::HOME_TOP => '首页轮播',
            self::HOME_RECOMMEND => '首页推荐',
            self::MINE_ENTRY => '我的页入口',
        };
    }

    /**
     * 全部标签映射：[值 => 中文标签]，用于下拉选项
     *
     * @return array<string, string>
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
