<?php

declare(strict_types=1);

namespace App\Http\Controllers\Console\V1;

use App\Http\Controllers\Controller;
use App\Services\Console\ConsoleFileService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 资料分类控制器
 * 台账：docs/04-API接口规范与登记表.md §四 API-CSL-FIL-001 ~ 004
 */
class FileCategoryController extends Controller
{
    public function __construct(private readonly ConsoleFileService $fileService)
    {
    }

    /** API-CSL-FIL-001 分类列表 */
    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success($this->fileService->categories($this->currentUserId($request)));
    }

    /** API-CSL-FIL-002 新建分类 */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:64'],
            'parent_id'   => ['nullable', 'integer', 'min:0'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ], [
            'name.required' => '请输入分类名称',
        ]);

        $result = $this->fileService->createCategory($this->currentUserId($request), $data);

        return ApiResponse::success($result, '分类创建成功');
    }

    /** API-CSL-FIL-003 更新分类 */
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['nullable', 'string', 'max:64'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ]);

        $this->fileService->updateCategory($id, $this->currentUserId($request), $data);

        return ApiResponse::ok('保存成功');
    }

    /** API-CSL-FIL-004 删除分类 */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->fileService->deleteCategory($id, $this->currentUserId($request));

        return ApiResponse::ok('分类已删除');
    }
}
