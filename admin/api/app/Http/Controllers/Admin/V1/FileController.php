<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Services\Admin\FileService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 文件资源管理（docs/04 §五 API-ADM-103）
 *   API-ADM-103 GET    /files          文件列表（分页 + 筛选）
 *   API-ADM-103 DELETE /files/{id}     删除（软删记录，不调 OSS 删除接口）
 *
 * 台账：docs/04 §五 API-ADM-103
 */
class FileController extends Controller
{
    public function __construct(private readonly FileService $fileService)
    {
    }

    /** API-ADM-103 文件列表 */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'page'      => $request->query('page'),
            'page_size' => $request->query('page_size'),
            'keyword'   => $request->query('keyword'),
            'biz_type'  => $request->query('biz_type'),
            'user_id'   => $request->query('user_id'),
            'storage'   => $request->query('storage'),
        ];

        $paginator = $this->fileService->list($filters);

        $list = collect($paginator->items())->map(fn ($f) => $this->fileService->toRow($f))->all();

        return ApiResponse::success([
            'list' => $list,
            'pagination' => [
                'page'        => $paginator->currentPage(),
                'page_size'   => $paginator->perPage(),
                'total'       => $paginator->total(),
                'total_pages' => $paginator->lastPage(),
            ],
        ]);
    }

    /** API-ADM-103 删除文件（仅软删记录，物理清理由 file:clean-deleted 定时任务负责） */
    public function destroy(int $id): JsonResponse
    {
        $this->fileService->delete($id);

        return ApiResponse::ok('删除成功');
    }
}
