<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Bank;

use App\Http\Controllers\Controller;
use App\Services\Api\FavoriteNoteService;
use App\Services\Api\QuestionPracticeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 题目与练习控制器
 * 台账：docs/04-API接口规范与登记表.md §三 API-QUE-001 ~ 005、API-FAV-001、API-NOTE-001、API-REC-001
 */
class QuestionPracticeController extends Controller
{
    public function __construct(
        private readonly QuestionPracticeService $service,
        private readonly FavoriteNoteService $favNoteService
    ) {
    }

    /** API-QUE-001 题目列表（练习取题） */
    public function index(Request $request, int $id): JsonResponse
    {
        [$page, $pageSize] = $this->pageParams($request);

        $filters = $request->only(['mode', 'chapter_id', 'question_type']);

        $paginator = $this->service->listQuestions(
            $this->currentUserId($request),
            $id,
            $filters,
            $page,
            $pageSize
        );

        return ApiResponse::paginate($paginator);
    }

    /** API-QUE-002 提交单题作答 */
    public function answer(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'answer'       => ['required', 'string'],
            'cost_seconds' => ['sometimes', 'integer', 'min:0'],
        ]);

        $result = $this->service->answer(
            $this->currentUserId($request),
            $id,
            (string) $data['answer'],
            isset($data['cost_seconds']) ? (int) $data['cost_seconds'] : null
        );

        return ApiResponse::success($result);
    }

    /** API-QUE-003 收藏 / 取消收藏 */
    public function favorite(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'favorite' => ['required', 'boolean'],
        ]);

        $this->service->favorite($this->currentUserId($request), $id, (bool) $data['favorite']);

        return ApiResponse::ok('操作成功');
    }

    /** API-QUE-004 写 / 改笔记 */
    public function note(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $this->service->saveNote($this->currentUserId($request), $id, (string) $data['content']);

        return ApiResponse::ok('已保存');
    }

    /** API-QUE-005 试题报错 */
    public function report(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'type'   => ['sometimes', 'integer', 'between:1,9'],
            'reason' => ['sometimes', 'string', 'max:500'],
            'images' => ['sometimes', 'array'],
            'images.*' => ['sometimes', 'string'],
        ]);

        $this->service->report($this->currentUserId($request), $id, $data);

        return ApiResponse::ok('已提交，感谢反馈');
    }

    /** API-FAV-001 我的收藏列表（默认每页 10 条，上限 50） */
    public function favorites(Request $request): JsonResponse
    {
        [$page, $pageSize] = $this->pageParams($request, 50, 10);

        $paginator = $this->favNoteService->favorites(
            $this->currentUserId($request),
            $request->only(['bank_id', 'keyword']),
            $page,
            $pageSize
        );

        return ApiResponse::paginate($paginator);
    }

    /** API-NOTE-001 我的笔记列表（默认每页 10 条，上限 50） */
    public function notes(Request $request): JsonResponse
    {
        [$page, $pageSize] = $this->pageParams($request, 50, 10);

        $paginator = $this->favNoteService->notes(
            $this->currentUserId($request),
            $request->only(['bank_id', 'keyword']),
            $page,
            $pageSize
        );

        return ApiResponse::paginate($paginator);
    }

    /** API-REC-001 练习记录列表 */
    public function practiceRecords(Request $request): JsonResponse
    {
        [$page, $pageSize] = $this->pageParams($request);

        $paginator = $this->service->practiceRecords(
            $this->currentUserId($request),
            $request->only(['bank_id', 'status']),
            $page,
            $pageSize
        );

        return ApiResponse::paginate($paginator);
    }
}
