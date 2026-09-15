<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Exam;

use App\Http\Controllers\Controller;
use App\Services\Api\ExamService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 客户端考试控制器
 * 台账：docs/04-API接口规范与登记表.md §三 API-EXM-001 ~ 005
 */
class ExamController extends Controller
{
    public function __construct(private readonly ExamService $service)
    {
    }

    /** API-EXM-001 发起 / 生成试卷 */
    public function storePaper(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bank_id'          => ['required', 'integer', 'min:1'],
            'question_count'   => ['required', 'integer', 'min:1'],
            'duration_minutes' => ['sometimes', 'integer', 'min:0'],
            'types'            => ['sometimes', 'array'],
            'types.*'          => ['sometimes', 'integer', 'between:1,5'],
        ]);

        return ApiResponse::success($this->service->createPaper($this->currentUserId($request), $data));
    }

    /** API-EXM-002 试卷详情（含题目） */
    public function showPaper(Request $request, int $id): JsonResponse
    {
        return ApiResponse::success($this->service->paperDetail($this->currentUserId($request), $id));
    }

    /** API-EXM-003 交卷（幂等） */
    public function submit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'paper_id'     => ['required', 'integer', 'min:1'],
            'answers'      => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer', 'min:1'],
            'answers.*.answer' => ['required', 'string'],
            'cost_seconds' => ['sometimes', 'integer', 'min:0'],
        ]);

        return ApiResponse::success($this->service->submit($this->currentUserId($request), $data));
    }

    /** API-EXM-004 成绩与试卷回顾 */
    public function showRecord(Request $request, int $id): JsonResponse
    {
        return ApiResponse::success($this->service->recordDetail($this->currentUserId($request), $id));
    }

    /** API-EXM-005 考试记录列表 */
    public function records(Request $request): JsonResponse
    {
        [$page, $pageSize] = $this->pageParams($request);

        $filters = $request->only(['bank_id', 'keyword']);

        $paginator = $this->service->paginateRecords($this->currentUserId($request), $filters, $page, $pageSize);

        return ApiResponse::paginate($paginator);
    }
}
