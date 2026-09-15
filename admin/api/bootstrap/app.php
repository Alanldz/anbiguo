<?php

declare(strict_types=1);

use App\Exceptions\BusinessException;
use App\Http\Middleware\ForceJsonResponseMiddleware;
use App\Http\Middleware\OperationLogMiddleware;
use App\Http\Middleware\RequestIdMiddleware;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // -------------------------------------------------------------
            // 总后台路由（docs/04-API接口规范与登记表.md §一 / §五）
            //   前缀 /admin-api/v1，守卫 auth:admin（scp=admin）
            //   鉴权在 routes/admin_api.php 内分组控制，login 除外
            // -------------------------------------------------------------
            Route::prefix('admin-api/v1')
                ->name('admin.')
                ->group(base_path('routes/admin_api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 全局中间件（按注册顺序执行）
        $middleware->prepend(RequestIdMiddleware::class);     // 最先：生成/透传链路 ID
        $middleware->append(ForceJsonResponseMiddleware::class);

        // 路由中间件别名
        $middleware->alias([
            'request.id'     => RequestIdMiddleware::class,
            'admin.active'   => EnsureAdminActiveMiddleware::class,
            'op.log'         => OperationLogMiddleware::class,
            'admin.ip'       => AdminIpWhitelist::class,  // 总后台 IP 白名单（config/admin.php ip_whitelist）
        ]);

        // API 请求走 Token 鉴权，不做 CSRF 校验
        $middleware->validateCsrfTokens(except: [
            'admin-api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // -------------------------------------------------------------
        // 统一异常 → 统一响应结构（docs/04 §2.2）
        // 业务异常返回自带错误码；系统异常生产环境统一 10500，不泄露细节
        // -------------------------------------------------------------
        $isApi = fn (Request $request) => $request->is('admin-api/*');

        // 业务异常（Service 层主动抛出）
        $exceptions->render(function (BusinessException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error(
                $e->getErrorCode(),
                $e->getMessage(),
                $e->getErrorData(),
                $e->getHttpStatus()
            );
        });

        // 参数校验失败
        $exceptions->render(function (ValidationException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            $firstMessage = collect($e->errors())->flatten()->first() ?: '参数校验失败';

            return ApiResponse::error(10422, $firstMessage, ['errors' => $e->errors()], 422);
        });

        // 未登录 / Token 失效 / Token 用错端
        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error(10401, '登录状态已失效，请重新登录', null, 401);
        });

        // 越权访问（守卫已通过但无权限 / 状态异常）
        $exceptions->render(function (AuthorizationException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error(10403, '没有操作权限', null, 403);
        });

        // 限流
        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error(10429, '操作过于频繁，请稍后再试', null, 429);
        });

        // 路由不存在
        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error(10404, '接口不存在', null, 404);
        });

        // 请求方法错误
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            return ApiResponse::error(10405, '请求方法不被允许', null, 405);
        });

        // 兜底：未捕获异常
        $exceptions->render(function (Throwable $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            $debug = (bool) config('app.debug');

            return ApiResponse::error(
                10500,
                $debug ? $e->getMessage() : '服务开小差了，请稍后再试',
                $debug ? [
                    'exception' => get_class($e),
                    'file'      => $e->getFile().':'.$e->getLine(),
                ] : null,
                500
            );
        });
    })
    ->create();
