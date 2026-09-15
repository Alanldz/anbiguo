<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\BusinessException;
use App\Models\SysAdmin;
use App\Support\ErrorCode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 管理员状态校验（docs/06 §八 第 1 条）
 *
 * 在 auth:admin 之后执行：确保已登录管理员处于可用状态。
 *   - status=2 禁用 → 40102
 *   - locked_until 未过期 → 40103
 * 这样即使 Token 仍有效，被禁用/锁定的账号也会立即失去操作权限。
 */
class EnsureAdminActiveMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user();

        if (! $admin instanceof SysAdmin) {
            return $next($request);
        }

        if ($admin->status !== SysAdmin::STATUS_NORMAL) {
            throw new BusinessException(ErrorCode::ADMIN_DISABLED, '该管理员账号已被禁用', null, 403);
        }

        if ($admin->locked_until !== null && $admin->locked_until->isFuture()) {
            throw new BusinessException(ErrorCode::ADMIN_LOCKED, '账号已锁定，请稍后再试', null, 403);
        }

        return $next($request);
    }
}
