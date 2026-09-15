<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * 请求上下文（链路追踪 ID）
 *
 * 由 RequestIdMiddleware 写入，供 ApiResponse、操作日志读取。
 * 使用静态属性而非容器绑定，保证在异常处理器中（此时中间件已出栈）依然可读。
 */
final class RequestContext
{
    private static string $requestId = '';

    public static function setRequestId(string $requestId): void
    {
        self::$requestId = $requestId;
    }

    public static function requestId(): string
    {
        if (self::$requestId === '') {
            self::$requestId = (string) Str::uuid();
        }

        return self::$requestId;
    }

    /** 重置（测试用） */
    public static function reset(): void
    {
        self::$requestId = '';
    }
}
