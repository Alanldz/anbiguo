<?php

declare(strict_types=1);

namespace App\Http\Controllers\Console\V1;

use App\Http\Controllers\Controller;
use App\Services\Console\ConsoleBankService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 章节控制器
 * 台账：docs/04-API接口规范与登记表.md §四 API-CSL-CHP-001 ~ 004
 */
class ChapterController extends Controller
{
    public function __construct(private readonly ConsoleBankService $bankService)
    {
    }

    /** API-CSL-CHP-001 章节列表（扁平数组） */
    public function index(Request $request, int $bankId): JsonResponse
    {
        return ApiResponse::success($this->bankService->chapters($bankId, $this->currentUserId($request)));
    }

    /** API-CSL-CHP-002 新建章节 */
    public function store(Request $request, int $bankId): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'parent_id'   => ['nullable', 'integer', 'min:0'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ], [
            'name.required' => '请输入章节名称',
        ]);

        $result = $this->bankService->createChapter($bankId, $this->currentUserId($request), $data);

        return ApiResponse::success($result, '章节创建成功');
    }

    /** API-CSL-CHP-003 更新章节 */
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name'       => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->bankService->updateChapter($id, $this->currentUserId($request), $data);

        return ApiResponse::ok('保存成功');
    }

    /** API-CSL-CHP-004 删除章节 */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->bankService->deleteChapter($id, $this->currentUserId($request));

        return ApiResponse::ok('章节已删除');
    }
}
