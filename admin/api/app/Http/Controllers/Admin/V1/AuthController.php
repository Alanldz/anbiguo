<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\SysAdmin;
use App\Services\Admin\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 管理员认证（docs/04 §五）
 *   API-ADM-001 POST   /auth/login   登录
 *   API-ADM-002 POST   /auth/logout  登出
 *   API-ADM-003 GET    /auth/me      当前管理员
 */
class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    /**
     * API-ADM-001 管理员登录
     * 签发 scp=admin 的 JWT（见 AuthService）。
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:32'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
        ]);

        $result = $this->authService->login(
            $data['username'],
            $data['password'],
            (string) $request->ip(),
            $request->userAgent()
        );

        return ApiResponse::success($result, '登录成功');
    }

    /**
     * API-ADM-002 管理员登出（jti 进黑名单）
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var SysAdmin $admin */
        $admin = $request->user();
        $token = (string) ($request->bearerToken() ?? '');

        $this->authService->logout($admin, $token);

        return ApiResponse::ok('已退出登录');
    }

    /**
     * API-ADM-003 当前管理员 + 角色 + 权限码
     */
    public function me(Request $request): JsonResponse
    {
        /** @var SysAdmin $admin */
        $admin = $request->user();

        return ApiResponse::success($this->authService->me($admin));
    }
}
