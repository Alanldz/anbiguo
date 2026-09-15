<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Services\Api\UserProfileService;
use App\Services\User\AuthService;
use App\Support\ApiResponse;
use App\Support\ErrorCode;
use App\Support\Jwt\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 客户端用户资料与学习空间控制器
 * 台账：docs/04-API接口规范与登记表.md §三 API-USER-001 ~ 004
 */
class ProfileController extends Controller
{
    public function __construct(
        private readonly UserProfileService $service,
        private readonly AuthService $authService
    ) {
    }

    /** API-USER-001 获取个人资料 */
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success($this->service->getProfile($this->currentUser($request)));
    }

    /** API-USER-002 更新个人资料 */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nickname'    => ['sometimes', 'string', 'max:32'],
            'avatar'     => ['sometimes', 'string', 'max:255'],
            'gender'     => ['sometimes', 'integer', 'in:0,1,2'],
            'birthday'   => ['sometimes', 'date'],
            'province'   => ['sometimes', 'string', 'max:32'],
            'city'       => ['sometimes', 'string', 'max:32'],
            'exam_target' => ['sometimes', 'string', 'max:64'],
            'bio'        => ['sometimes', 'string', 'max:255'],
        ]);

        $this->service->updateProfile($this->currentUser($request), $data);

        return ApiResponse::ok('保存成功');
    }

    /** API-USER-003 我的学习空间统计 */
    public function studySummary(Request $request): JsonResponse
    {
        return ApiResponse::success($this->service->getStudySummary($this->currentUserId($request)));
    }

    /**
     * API-USER-004 账号注销
     *
     * 1) 校验 confirm 必须为字面 true；
     * 2) 吊销当前令牌（复用 logout 的黑名单逻辑，保持一致）；
     * 3) 置账号 status=CANCELING，EnsureUserActiveMiddleware 随即拦截所有后续请求（即全部 Token 失效）。
     * 注销原因本期不落库。
     */
    public function cancel(Request $request): JsonResponse
    {
        $data = $request->validate([
            'confirm' => ['required', 'boolean'],
            'reason'  => ['sometimes', 'string', 'max:200'],
        ]);

        if ($data['confirm'] !== true) {
            throw BusinessException::of(ErrorCode::PARAM_INVALID, '请确认注销：confirm 必须为 true');
        }

        // 注销原因暂不落库，后续如需审计再加字段
        $user = $this->currentUser($request);

        // 吊销当前令牌（与 AuthController::logout 一致）
        $token = (string) $request->bearerToken();
        $secret = (string) config('auth.guards.client.secret', '');
        try {
            $parsed = TokenService::parse($token, $secret, 'client');
            $expiresIn = max((int) ($parsed['payload']['exp'] ?? 0) - time(), 60);
            $this->authService->logout($parsed['jti'], $expiresIn);
        } catch (\Throwable) {
            // 令牌无效也继续走注销流程（账号状态本身会使其彻底失效）
        }

        $this->authService->cancel((int) $user->id);

        return ApiResponse::success([
            'canceled' => true,
            'message'  => '账号已注销',
        ]);
    }
}
