<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Import;

use App\Http\Controllers\Controller;
use App\Services\Api\ImportService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 客户端导入控制器（本期同步占位）
 * 台账：docs/04-API接口规范与登记表.md §三 API-IMP-001 ~ 005
 */
class ImportController extends Controller
{
    public function __construct(private readonly ImportService $service)
    {
    }

    /** API-IMP-001 上传文档导题 */
    public function upload(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file_id'     => ['required', 'integer', 'min:1'],
            'bank_id'     => ['sometimes', 'integer', 'min:0'],
            'title'       => ['sometimes', 'string', 'max:120'],
            'split_answer' => ['sometimes', 'boolean'],
        ]);

        return ApiResponse::success($this->service->upload($this->currentUserId($request), $data));
    }

    /** API-IMP-002 查询解析进度 */
    public function tasksShow(Request $request, int $id): JsonResponse
    {
        return ApiResponse::success($this->service->tasksShow($this->currentUserId($request), $id));
    }

    /** API-IMP-003 下载导入模板 */
    public function template(Request $request): JsonResponse
    {
        return ApiResponse::success($this->service->template());
    }

    /** API-IMP-004 手动录入题目（占位） */
    public function manual(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bank_id'  => ['required', 'integer', 'min:1'],
            'type'     => ['required', 'integer', 'between:1,5'],
            'title'    => ['required', 'string', 'max:2000'],
            'options'  => ['sometimes', 'array'],
            'options.*.key' => ['sometimes', 'string', 'max:2'],
            'options.*.content' => ['sometimes', 'string', 'max:1000'],
            'answer'   => ['required', 'string', 'max:500'],
            'analysis' => ['sometimes', 'string'],
        ]);

        return ApiResponse::success($this->service->manual($this->currentUserId($request), $data));
    }

    /** API-IMP-005 拍照录题（OCR，占位） */
    public function ocr(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file_id' => ['sometimes', 'integer', 'min:1'],
            'bank_id' => ['sometimes', 'integer', 'min:0'],
        ]);

        return ApiResponse::success($this->service->ocr($this->currentUserId($request), $data));
    }
}
