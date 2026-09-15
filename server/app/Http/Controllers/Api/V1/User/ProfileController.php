<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Services\Api\UserProfileService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 客户端用户资料与学习空间控制器
 * 台账：docs/04-API接口规范与登记表.md §三 API-USER-001 ~ 003
 */
class ProfileController extends Controller
{
    public function __construct(private readonly UserProfileService $service)
    {
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
}
