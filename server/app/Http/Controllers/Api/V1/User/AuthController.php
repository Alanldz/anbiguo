<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MobileLoginRequest;
use App\Http\Requests\Auth\SendSmsCodeRequest;
use App\Http\Requests\Auth\WechatLoginRequest;
use App\Models\User;
use App\Services\User\AuthService;
use App\Support\ApiResponse;
use App\Support\Jwt\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 客户端认证控制器
 * 台账：docs/04-API接口规范与登记表.md §三 API-AUTH-001 ~ 005
 */
class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    /** API-AUTH-001 发送短信验证码 */
    public function sendSmsCode(SendSmsCodeRequest $request): JsonResponse
    {
        $result = $this->authService->sendSmsCode((string) $request->input('mobile'));

        return ApiResponse::success($result, '验证码已发送');
    }

    /** API-AUTH-002 手机号登录 / 注册 */
    public function login(MobileLoginRequest $request): JsonResponse
    {
        $result = $this->authService->loginByMobile(
            (string) $request->input('mobile'),
            (string) $request->input('code')
        );

        return ApiResponse::success($result, '登录成功');
    }

    /** API-AUTH-003 微信小程序登录 */
    public function wechatLogin(WechatLoginRequest $request): JsonResponse
    {
        $result = $this->authService->loginByWechat((string) $request->input('code'));

        return ApiResponse::success($result, '登录成功');
    }

    /** API-AUTH-004 刷新 Token */
    public function refresh(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authService->touchLogin($user, (string) $request->ip());

        return ApiResponse::success($this->authService->refresh($user));
    }

    /** API-AUTH-005 退出登录 */
    public function logout(Request $request): JsonResponse
    {
        $token = (string) $request->bearerToken();
        $secret = (string) config('auth.guards.client.secret', '');

        // 解析出 jti 并加入黑名单；解析失败（Token 已过期）也视为退出成功
        try {
            $parsed = TokenService::parse($token, $secret, 'client');
            $expiresIn = max((int) ($parsed['payload']['exp'] ?? 0) - time(), 60);
            $this->authService->logout($parsed['jti'], $expiresIn);
        } catch (\Throwable) {
            // 无需处理：Token 已不可用
        }

        return ApiResponse::ok('已退出登录');
    }
}
