<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Exceptions\BusinessException;
use App\Models\User;
use App\Support\ErrorCode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 客户端用户状态校验（挂在 auth:client 之后）
 *
 * 目的：守卫只负责验证 Token 有效性，本中间件负责「账号是否还能用」这一业务判断。
 *      被禁用 / 注销中的账号即便 Token 未过期，也必须立刻失效。
 */
class EnsureUserActiveMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new BusinessException(ErrorCode::UNAUTHORIZED, '请先登录', null, 401);
        }

        $status = (int) $user->status;

        if ($status === UserStatus::DISABLED->value) {
            throw new BusinessException(ErrorCode::ACCOUNT_DISABLED, '', null, 403);
        }

        if (in_array($status, [UserStatus::CANCELING->value, UserStatus::CANCELED->value], true)) {
            throw new BusinessException(ErrorCode::UNAUTHORIZED, '账号已注销', null, 401);
        }

        return $next($request);
    }
}
