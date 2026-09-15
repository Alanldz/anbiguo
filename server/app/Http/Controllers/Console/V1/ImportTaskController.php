<?php

declare(strict_types=1);

namespace App\Http\Controllers\Console\V1;

use App\Http\Controllers\Controller;
use App\Services\Console\ConsoleImportService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 导入任务控制器
 * 台账：docs/04-API接口规范与登记表.md §四 API-CSL-IMP-001 ~ 005
 */
class ImportTaskController extends Controller
{
    public function __construct(private readonly ConsoleImportService $importService)
    {
    }

    /** API-CSL-IMP-001 创建导入任务（同步占位） */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file_id'     => ['required', 'integer', 'min:1'],
            'bank_id'     => ['nullable', 'integer', 'min:0'],
            'bank_title'  => ['nullable', 'string', 'max:120'],
            'import_mode' => ['nullable', 'integer', Rule::in([1, 2, 3, 4])],
        ], [
            'file_id.required' => '请先上传源文件',
        ]);

        $result = $this->importService->create($this->currentUserId($request), $data);

        return ApiResponse::success($result, '导入任务已创建');
    }

    /** API-CSL-IMP-002 导入任务列表 */
    public function index(Request $request): JsonResponse
    {
        [$page, $pageSize] = $this->pageParams($request);

        $paginator = $this->importService->paginate(
            $this->currentUserId($request),
            $request->only(['status']),
            $page,
            $pageSize
        );

        return ApiResponse::paginate($paginator);
    }

    /** API-CSL-IMP-003 导入任务详情 */
    public function show(Request $request, int $id): JsonResponse
    {
        return ApiResponse::success($this->importService->detail($id, $this->currentUserId($request)));
    }

    /** API-CSL-IMP-004 删除导入任务 */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->importService->delete($id, $this->currentUserId($request));

        return ApiResponse::ok('导入任务已删除');
    }

    /** API-CSL-IMP-005 导入模板说明 */
    public function template(Request $request): JsonResponse
    {
        return ApiResponse::success($this->importService->template());
    }
}
