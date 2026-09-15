<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * 请求上下文（链路追踪 ID / 客户端平台）
 *
 * 由 RequestIdMiddleware 与 PlatformHeaderMiddleware 写入，供 ApiResponse、日志、操作日志读取。
 * 使用静态属性而非容器绑定，保证在异常处理器中（此时中间件已出栈）依然可读。
 */
final class RequestContext
{
    private static string $requestId = '';

    private static string $platform = '';

    private static string $clientVersion = '';

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

    public static function setPlatform(string $platform): void
    {
        self::$platform = $platform;
    }

    /** 客户端平台：mp-weixin / app-android / h5 / console / admin */
    public static function platform(): string
    {
        return self::$platform;
    }

    public static function setClientVersion(string $version): void
    {
        self::$clientVersion = $version;
    }

    public static function clientVersion(): string
    {
        return self::$clientVersion;
    }

    /** 重置（测试用） */
    public static function reset(): void
    {
        self::$requestId = '';
        self::$platform = '';
        self::$clientVersion = '';
    }
}
