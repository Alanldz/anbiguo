<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Bank;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bank\StoreQuestionBankRequest;
use App\Http\Requests\Bank\UpdateQuestionBankRequest;
use App\Http\Resources\QuestionBankResource;
use App\Services\Bank\QuestionBankService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 题库控制器
 * 台账：docs/04-API接口规范与登记表.md §三 API-BANK-002 ~ 007
 */
class QuestionBankController extends Controller
{
    public function __construct(private readonly QuestionBankService $bankService)
    {
    }

    /** API-BANK-002 我的题库列表 */
    public function index(Request $request): JsonResponse
    {
        [$page, $pageSize] = $this->pageParams($request);

        $paginator = $this->bankService->paginateMine(
            $this->currentUserId($request),
            $request->only(['keyword', 'source_type', 'category_id']),
            $page,
            $pageSize
        );

        return ApiResponse::paginate($paginator, QuestionBankResource::class);
    }

    /** API-BANK-004 创建题库 */
    public function store(StoreQuestionBankRequest $request): JsonResponse
    {
        $bank = $this->bankService->create($this->currentUser($request), $request->validated());

        return ApiResponse::success(
            new QuestionBankResource($bank),
            '题库创建成功，审核通过后其他用户可见'
        );
    }

    /** API-BANK-003 题库详情 */
    public function show(Request $request, int $id): JsonResponse
    {
        $bank = $this->bankService->detail($id, $this->currentUserId($request));

        return ApiResponse::success(new QuestionBankResource($bank));
    }

    /** API-BANK-005 更新 / 重命名题库 */
    public function update(UpdateQuestionBankRequest $request, int $id): JsonResponse
    {
        $bank = $this->bankService->detail($id, $this->currentUserId($request));

        $bank = $this->bankService->update($bank, $this->currentUserId($request), $request->validated());

        return ApiResponse::success(new QuestionBankResource($bank), '保存成功');
    }

    /** API-BANK-006 删除题库 */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $bank = $this->bankService->detail($id, $this->currentUserId($request));

        $this->bankService->delete($bank, $this->currentUserId($request));

        return ApiResponse::ok('题库已删除');
    }

    /** API-BANK-007 题库市场列表 */
    public function market(Request $request): JsonResponse
    {
        [$page, $pageSize] = $this->pageParams($request);

        $paginator = $this->bankService->paginateMarket(
            $request->only(['category_id', 'keyword', 'charge_type']),
            $page,
            $pageSize
        );

        return ApiResponse::paginate($paginator, QuestionBankResource::class);
    }
}
