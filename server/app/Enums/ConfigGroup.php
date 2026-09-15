<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：系统配置分组 ｜ 对应字段：sys_configs.group_code ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum ConfigGroup: string
{
    /** 短信 */
    case SMS = 'sms';

    /** 微信 */
    case WECHAT = 'wechat';

    /** 支付 */
    case PAYMENT = 'payment';

    /** 存储 */
    case STORAGE = 'storage';

    /** AI */
    case AI = 'ai';

    /** OCR */
    case OCR = 'ocr';

    /** 站点 */
    case SITE = 'site';

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::SMS => '短信',
            self::WECHAT => '微信',
            self::PAYMENT => '支付',
            self::STORAGE => '存储',
            self::AI => 'AI',
            self::OCR => 'OCR',
            self::SITE => '站点',
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
