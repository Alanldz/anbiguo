<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：试题报错类型 ｜ 对应字段：question_reports.report_type ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum QuestionReportType: int
{
    /** 答案错误 */
    case ANSWER_WRONG = 1;

    /** 解析错误 */
    case ANALYSIS_WRONG = 2;

    /** 题干错误 */
    case STEM_WRONG = 3;

    /** 图片错误 */
    case IMAGE_WRONG = 4;

    /** 题目重复 */
    case DUPLICATE = 5;

    /** 其他 */
    case OTHER = 9;

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::ANSWER_WRONG => '答案错误',
            self::ANALYSIS_WRONG => '解析错误',
            self::STEM_WRONG => '题干错误',
            self::IMAGE_WRONG => '图片错误',
            self::DUPLICATE => '题目重复',
            self::OTHER => '其他',
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
