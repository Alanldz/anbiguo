<?php

declare(strict_types=1);

namespace App\Http\Controllers\Console\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Console\ConsoleAuthService;
use App\Support\ApiResponse;
use App\Support\Jwt\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 用户后台认证控制器
 * 台账：docs/04-API接口规范与登记表.md §四 API-CSL-AUTH-001 ~ 003
 */
class AuthController extends Controller
{
    public function __construct(private readonly ConsoleAuthService $authService)
    {
    }

    /** API-CSL-AUTH-001 用户后台登录（密码 或 验证码二选一） */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile'     => ['required', 'string', 'regex:/^1[3-9]\d{9}$/'],
            'password'   => ['required_without:code', 'nullable', 'string', 'min:6', 'max:64'],
            'code'       => ['required_without:password', 'nullable', 'string', 'digits:6'],
            'login_type' => ['nullable', 'integer', Rule::in([1, 2])],   // 1=密码 2=验证码
        ], [
            'mobile.required' => '请输入手机号',
            'mobile.regex'    => '手机号格式不正确',
            'password.required_without' => '请输入密码或验证码',
            'code.required_without'     => '请输入密码或验证码',
        ]);

        $mobile = (string) $data['mobile'];

        $result = ! empty($data['code'])
            ? $this->authService->loginByCode($mobile, (string) $data['code'])
            : $this->authService->loginByPassword($mobile, (string) $data['password']);

        return ApiResponse::success($result, '登录成功');
    }

    /** API-CSL-AUTH-002 退出登录 */
    public function logout(Request $request): JsonResponse
    {
        $token = (string) $request->bearerToken();
        $secret = (string) config('auth.guards.console.secret', '');

        try {
            $parsed = TokenService::parse($token, $secret, 'console');
            $expiresIn = max((int) ($parsed['payload']['exp'] ?? 0) - time(), 60);
            $this->authService->logout($parsed['jti'], $expiresIn);
        } catch (\Throwable) {
            // Token 已不可用，视为退出成功
        }

        return ApiResponse::ok('已退出登录');
    }

    /** API-CSL-AUTH-003 当前登录用户信息 */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->currentUser($request);

        return ApiResponse::success($this->authService->me($user));
    }
}
