<?php

declare(strict_types=1);

namespace App\Http\Controllers\Console\V1;

use App\Http\Controllers\Controller;
use App\Services\Console\ConsoleFileService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 文件资源控制器
 * 台账：docs/04-API接口规范与登记表.md §四 API-CSL-FIL-005 ~ 009
 */
class FileAssetController extends Controller
{
    public function __construct(private readonly ConsoleFileService $fileService)
    {
    }

    /** API-CSL-FIL-005 资料列表 */
    public function index(Request $request): JsonResponse
    {
        [$page, $pageSize] = $this->pageParams($request);

        $paginator = $this->fileService->paginateAssets(
            $this->currentUserId($request),
            $request->only(['keyword', 'category_id', 'biz_type']),
            $page,
            $pageSize
        );

        return ApiResponse::paginate($paginator);
    }

    /** API-CSL-FIL-006 上传后登记 */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'object_key'   => ['required', 'string', 'max:500'],
            'origin_name'  => ['required', 'string', 'max:255'],
            'file_ext'     => ['nullable', 'string', 'max:16'],
            'file_size'    => ['nullable', 'integer', 'min:0'],
            'file_hash'    => ['nullable', 'string', 'max:64'],
            'mime_type'    => ['nullable', 'string', 'max:100'],
            'category_id'  => ['nullable', 'integer', 'min:0'],
            'biz_type'     => ['nullable', 'integer', Rule::in([1, 2, 3, 4, 5, 6, 9])],
            'is_public'    => ['nullable', 'integer', Rule::in([0, 1])],
        ], [
            'object_key.required'  => '缺少文件对象标识',
            'origin_name.required' => '缺少文件名',
        ]);

        return ApiResponse::success(
            $this->fileService->registerUploadedFile($this->currentUserId($request), $data),
            '登记成功'
        );
    }

    /** API-CSL-FIL-007 删除资料 */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->fileService->deleteAsset($id, $this->currentUserId($request));

        return ApiResponse::ok('资料已删除');
    }

    /** API-CSL-FIL-008 获取下载地址 */
    public function url(Request $request, int $id): JsonResponse
    {
        return ApiResponse::success($this->fileService->url($this->currentUser($request), $id));
    }

    /** API-CSL-FIL-009 七牛直传凭证 */
    public function uploadToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'biz_type'    => ['required', 'integer', Rule::in([1, 2, 3, 4, 5, 6, 9])],
            'file_ext'    => ['required', 'string', 'max:16'],
            'file_name'   => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'min:0'],
        ], [
            'biz_type.required' => '请选择文件业务类型',
            'file_ext.required' => '缺少文件扩展名',
            'file_name.required' => '缺少文件名',
        ]);

        return ApiResponse::success($this->fileService->uploadToken($this->currentUser($request), $data));
    }
}
