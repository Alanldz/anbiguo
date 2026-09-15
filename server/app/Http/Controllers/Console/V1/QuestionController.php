<?php

declare(strict_types=1);

namespace App\Http\Controllers\Console\V1;

use App\Http\Controllers\Controller;
use App\Services\Console\ConsoleQuestionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 题目管理控制器
 * 台账：docs/04-API接口规范与登记表.md §四 API-CSL-QST-001 ~ 007
 */
class QuestionController extends Controller
{
    public function __construct(private readonly ConsoleQuestionService $questionService)
    {
    }

    /** API-CSL-QST-001 题目列表 */
    public function index(Request $request, int $bankId): JsonResponse
    {
        [$page, $pageSize] = $this->pageParams($request);

        $paginator = $this->questionService->paginate(
            $bankId,
            $this->currentUserId($request),
            $request->only(['keyword', 'question_type', 'difficulty', 'chapter_id', 'status']),
            $page,
            $pageSize
        );

        return ApiResponse::paginate($paginator);
    }

    /** API-CSL-QST-003 新增题目 */
    public function store(Request $request, int $bankId): JsonResponse
    {
        $data = $this->validateQuestion($request);

        $result = $this->questionService->create($bankId, $this->currentUserId($request), $data);

        return ApiResponse::success($result, '题目添加成功');
    }

    /** API-CSL-QST-002 题目详情 */
    public function show(Request $request, int $id): JsonResponse
    {
        return ApiResponse::success($this->questionService->detail($id, $this->currentUserId($request)));
    }

    /** API-CSL-QST-004 更新题目 */
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $this->validateQuestion($request, true);

        $this->questionService->update($id, $this->currentUserId($request), $data);

        return ApiResponse::ok('保存成功');
    }

    /** API-CSL-QST-005 删除题目 */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->questionService->delete($id, $this->currentUserId($request));

        return ApiResponse::ok('题目已删除');
    }

    /** API-CSL-QST-006 批量删除 */
    public function batchDelete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'min:1'],
        ], [
            'ids.required' => '请选择要删除的题目',
        ]);

        $this->questionService->batchDelete($this->currentUserId($request), $data['ids']);

        return ApiResponse::ok('已删除所选题目');
    }

    /** API-CSL-QST-007 批量移动章节 */
    public function batchMove(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids'        => ['required', 'array'],
            'ids.*'      => ['integer', 'min:1'],
            'chapter_id' => ['required', 'integer', 'min:0'],
        ], [
            'ids.required'      => '请选择要移动的题目',
            'chapter_id.required' => '请选择目标章节',
        ]);

        $this->questionService->batchMove($this->currentUserId($request), $data['ids'], (int) $data['chapter_id']);

        return ApiResponse::ok('已移动所选题目');
    }

    /** 题目入参校验 */
    private function validateQuestion(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'question_type' => [$isUpdate ? 'nullable' : 'required', 'integer', Rule::in([1, 2, 3, 4, 5])],
            'stem'          => [$isUpdate ? 'nullable' : 'required', 'string'],
            'analysis'      => ['nullable', 'string'],
            'answer'        => [$isUpdate ? 'nullable' : 'required', 'string', 'max:500'],
            'difficulty'    => ['nullable', 'integer', Rule::in([1, 2, 3])],
            'score'         => ['nullable', 'numeric', 'min:0'],
            'chapter_id'    => ['nullable', 'integer', 'min:0'],
            'sort_order'    => ['nullable', 'integer', 'min:0'],
            'options'       => ['nullable', 'array'],
            'options.*.option_key' => ['required_with:options', 'string', 'max:2'],
            'options.*.content'    => ['required_with:options', 'string'],
            'options.*.is_correct' => ['required_with:options', 'integer', Rule::in([0, 1])],
            'options.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];

        return $request->validate($rules);
    }
}
