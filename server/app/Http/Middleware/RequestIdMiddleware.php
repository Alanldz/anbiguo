<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\RequestContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * 链路追踪 ID
 *
 * 优先透传前端生成的 X-Request-Id，缺省则服务端生成 UUID。
 * 写入 RequestContext（响应体与日志使用）并回写响应头，便于前后端联查。
 */
class RequestIdMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = trim((string) $request->header('X-Request-Id', ''));

        if ($requestId === '' || mb_strlen($requestId) > 64) {
            $requestId = (string) Str::uuid();
        }

        RequestContext::setRequestId($requestId);
        $request->attributes->set('request_id', $requestId);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
