<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 强制 JSON 响应
 *
 * 目的：避免 Laravel 在参数校验失败等场景下返回 HTML 重定向，
 *      保证任何情况下前端拿到的都是统一 JSON 结构（docs/04 §2.2）。
 */
class ForceJsonResponseMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/*', 'console-api/*')) {
            $request->headers->set('Accept', 'application/json');

            if (! $request->isMethod('GET')) {
                $request->headers->set('X-Requested-With', 'XMLHttpRequest');
            }
        }

        return $next($request);
    }
}
