<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Models\SysConfig;
use App\Services\Admin\ConfigService;
use App\Support\ApiResponse;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 配置中心（docs/04 §五 API-ADM-CFG-*）
 *   API-ADM-040 GET /configs    配置列表（group 为空返回全部 + 分组清单）
 *   API-ADM-041 PUT /configs/{id} 修改配置（密文加密 + 清缓存）
 */
class ConfigController extends Controller
{
    public function __construct(private readonly ConfigService $configService)
    {
    }

    /** API-ADM-040 配置列表 */
    public function index(Request $request): JsonResponse
    {
        $group = $request->query('group');
        $group = is_string($group) ? $group : null;

        return ApiResponse::success($this->configService->list($group));
    }

    /** API-ADM-041 修改配置 */
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'value' => ['required', 'string'],
        ]);

        $result = $this->configService->update($id, $data['value']);

        return ApiResponse::success($result, '保存成功');
    }

    /** API-ADM-101 测试配置连通性（sys:config:test） */
    public function test(int $id): JsonResponse
    {
        $result = $this->configService->test($id);

        return ApiResponse::success($result, $result['ok'] ? '连通性正常' : '连通性探测未通过');
    }
}
