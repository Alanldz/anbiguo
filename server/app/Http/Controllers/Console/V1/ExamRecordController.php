<?php

declare(strict_types=1);

namespace App\Http\Controllers\Console\V1;

use App\Http\Controllers\Controller;
use App\Services\Console\ConsoleExamService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 考试记录控制器
 * 台账：docs/04-API接口规范与登记表.md §四 API-CSL-EXM-001 ~ 002
 */
class ExamRecordController extends Controller
{
    public function __construct(private readonly ConsoleExamService $examService)
    {
    }

    /** API-CSL-EXM-001 考试记录列表 */
    public function index(Request $request): JsonResponse
    {
        [$page, $pageSize] = $this->pageParams($request);

        $paginator = $this->examService->paginate(
            $this->currentUserId($request),
            $request->only(['bank_id', 'keyword']),
            $page,
            $pageSize
        );

        return ApiResponse::paginate($paginator);
    }

    /** API-CSL-EXM-002 考试记录详情 */
    public function show(Request $request, int $id): JsonResponse
    {
        return ApiResponse::success($this->examService->detail($id, $this->currentUserId($request)));
    }
}
