<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\RequestContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 客户端平台标识（docs/04 §2.1）
 *
 * 读取 X-Client-Platform 与 X-Client-Version 并写入 RequestContext，
 * 供埋点、限流、版本兼容判断与操作日志使用。
 *
 * 允许值：mp-weixin / app-android / h5 / console / admin
 */
class PlatformHeaderMiddleware
{
    private const ALLOWED_PLATFORMS = [
        'mp-weixin',
        'app-android',
        'h5',
        'console',
        'admin',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $platform = trim((string) $request->header('X-Client-Platform', ''));

        if ($platform !== '' && in_array($platform, self::ALLOWED_PLATFORMS, true)) {
            RequestContext::setPlatform($platform);
        }

        RequestContext::setClientVersion(trim((string) $request->header('X-Client-Version', '')));

        return $next($request);
    }
}
