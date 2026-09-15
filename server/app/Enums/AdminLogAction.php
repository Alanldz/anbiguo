<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 枚举：管理员操作日志动作 ｜ 对应字段：sys_logs.action ｜ 登记：docs/03-数据库设计规范.md §三
 */
enum AdminLogAction: string
{
    /** 创建 */
    case CREATE = 'create';

    /** 更新 */
    case UPDATE = 'update';

    /** 删除 */
    case DELETE = 'delete';

    /** 审核 */
    case AUDIT = 'audit';

    /** 登录 */
    case LOGIN = 'login';

    /** 登出 */
    case LOGOUT = 'logout';

    /**
     * 中文标签，用于后台展示与接口返回
     */
    public function label(): string
    {
        return match ($this) {
            self::CREATE => '创建',
            self::UPDATE => '更新',
            self::DELETE => '删除',
            self::AUDIT => '审核',
            self::LOGIN => '登录',
            self::LOGOUT => '登出',
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
