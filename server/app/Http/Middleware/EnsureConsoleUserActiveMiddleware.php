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
 * 用户后台账号状态校验（挂在 auth:console 之后）
 *
 * 与客户端中间件逻辑一致，但独立命名：
 * 两端的错误提示与后续扩展（如后台专属操作审计）可能分化，保持解耦便于各自演进。
 */
class EnsureConsoleUserActiveMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new BusinessException(ErrorCode::UNAUTHORIZED, '请先登录', null, 401);
        }

        if ((int) $user->status !== UserStatus::NORMAL->value) {
            throw new BusinessException(ErrorCode::ACCOUNT_DISABLED, '账号状态异常，请联系客服', null, 403);
        }

        return $next($request);
    }
}
