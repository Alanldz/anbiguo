<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\File;

use App\Http\Controllers\Controller;
use App\Http\Requests\File\CompleteUploadRequest;
use App\Http\Requests\File\UploadTokenRequest;
use App\Http\Resources\FileAssetResource;
use App\Models\FileAsset;
use App\Services\File\FileAssetService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 文件控制器（七牛云直传）
 * 台账：docs/04-API接口规范与登记表.md §三 API-FIL-001 ~ 004
 */
class FileController extends Controller
{
    public function __construct(private readonly FileAssetService $fileService)
    {
    }

    /** API-FIL-001 获取 OSS 直传凭证 */
    public function uploadToken(UploadTokenRequest $request): JsonResponse
    {
        $result = $this->fileService->issueUploadToken(
            $this->currentUser($request),
            $request->validated()
        );

        return ApiResponse::success($result);
    }

    /** API-FIL-002 上传完成回调登记 */
    public function complete(CompleteUploadRequest $request): JsonResponse
    {
        $asset = $this->fileService->complete(
            $this->currentUser($request),
            $request->validated()
        );

        return ApiResponse::success(new FileAssetResource($asset), '上传成功');
    }

    /** API-FIL-003 学习资料列表 */
    public function index(Request $request): JsonResponse
    {
        [$page, $pageSize] = $this->pageParams($request);

        $paginator = $this->fileService->paginateAssets(
            $this->currentUser($request),
            $request->only(['biz_type', 'bank_id', 'category_id', 'keyword']),
            $page,
            $pageSize
        );

        return ApiResponse::paginate($paginator, FileAssetResource::class);
    }

    /** API-FIL-004 获取私有文件签名下载地址 */
    public function signedUrl(Request $request, int $id): JsonResponse
    {
        $asset = FileAsset::whereKey($id)->whereNull('deleted_at')->first();

        if ($asset === null) {
            return ApiResponse::error(60004);
        }

        $url = $this->fileService->signedUrl($this->currentUser($request), $asset);

        return ApiResponse::success([
            'id'         => $asset->id,
            'url'        => $url,
            'expires_in' => 900,
        ]);
    }
}
