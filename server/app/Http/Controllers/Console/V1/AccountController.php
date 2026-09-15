<?php

declare(strict_types=1);

namespace App\Http\Controllers\Console\V1;

use App\Http\Controllers\Controller;
use App\Services\Console\ConsoleAccountService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 账号设置控制器
 * 台账：docs/04-API接口规范与登记表.md §四 API-CSL-ACC-001 ~ 004
 */
class AccountController extends Controller
{
    public function __construct(private readonly ConsoleAccountService $accountService)
    {
    }

    /** API-CSL-ACC-001 个人资料 */
    public function profile(Request $request): JsonResponse
    {
        return ApiResponse::success($this->accountService->profile($this->currentUserId($request)));
    }

    /** API-CSL-ACC-002 更新资料 */
    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nickname'    => ['nullable', 'string', 'max:32'],
            'avatar'      => ['nullable', 'string', 'max:255'],
            'gender'      => ['nullable', 'integer', Rule::in([0, 1, 2])],
            'birthday'    => ['nullable', 'date'],
            'province'    => ['nullable', 'string', 'max:32'],
            'city'        => ['nullable', 'string', 'max:32'],
            'exam_target' => ['nullable', 'string', 'max:64'],
            'bio'         => ['nullable', 'string', 'max:255'],
        ]);

        return ApiResponse::success(
            $this->accountService->updateProfile($this->currentUserId($request), $data),
            '资料已更新'
        );
    }

    /** API-CSL-ACC-003 修改密码 */
    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'old_password' => ['nullable', 'string', 'min:6', 'max:64'],
            'new_password' => ['required', 'string', 'min:6', 'max:64'],
        ], [
            'new_password.required' => '请输入新密码',
        ]);

        $this->accountService->changePassword(
            $this->currentUserId($request),
            $data['old_password'] ?? null,
            (string) $data['new_password']
        );

        return ApiResponse::ok('密码修改成功');
    }

    /** API-CSL-ACC-004 换绑手机 */
    public function changeMobile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'new_mobile' => ['required', 'string', 'regex:/^1[3-9]\d{9}$/'],
            'code'       => ['required', 'string', 'digits:6'],
        ], [
            'new_mobile.required' => '请输入新手机号',
            'new_mobile.regex'    => '手机号格式不正确',
            'code.required'       => '请输入验证码',
        ]);

        $this->accountService->changeMobile(
            $this->currentUserId($request),
            (string) $data['new_mobile'],
            (string) $data['code']
        );

        return ApiResponse::ok('手机号已更新');
    }
}
