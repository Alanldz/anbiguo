<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Wrong;

use App\Http\Controllers\Controller;
use App\Services\Api\WrongQuestionService;
use App\Support\ApiResponse;
use App\Support\ErrorCode;
use App\Exceptions\BusinessException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 客户端错题控制器
 * 台账：docs/04-API接口规范与登记表.md §三 API-WRG-001 ~ 002
 *      docs/04-API接口规范与登记表.md §二 API-MST-001 ~ 002、API-ERR-001
 */
class WrongQuestionController extends Controller
{
    public function __construct(private readonly WrongQuestionService $service)
    {
    }

    /** API-WRG-001 错题列表 */
    public function index(Request $request): JsonResponse
    {
        [$page, $pageSize] = $this->pageParams($request);

        $filters = $request->only(['bank_id', 'question_type']);

        $paginator = $this->service->paginate($this->currentUserId($request), $filters, $page, $pageSize);

        return ApiResponse::paginate($paginator);
    }

    /** API-WRG-002 移除错题 */
    public function remove(Request $request, int $id): JsonResponse
    {
        $this->service->remove($id, $this->currentUserId($request));

        return ApiResponse::ok('已移除');
    }

    /** API-MST-001 我的斩题列表（已掌握题目，分页） */
    public function mastered(Request $request): JsonResponse
    {
        [$page, $pageSize] = $this->pageParams($request, 50, 10);

        $paginator = $this->service->masteredPaginate(
            $this->currentUserId($request),
            $request->only(['bank_id', 'keyword']),
            $page,
            $pageSize
        );

        return ApiResponse::paginate($paginator);
    }

    /** API-MST-002 找回已掌握题目 */
    public function restoreMastered(Request $request, int $id): JsonResponse
    {
        $this->service->restoreMastered($id, $this->currentUserId($request));

        return ApiResponse::success(['restored' => true]);
    }

    /** API-ERR-001 易错题集（按题库维度，correct_rate 升序） */
    public function errorProne(Request $request): JsonResponse
    {
        $bankId = (int) $request->input('bank_id', 0);

        // 易错题按题库维度查看，bank_id 必填
        if ($bankId <= 0) {
            throw new BusinessException(ErrorCode::PARAM_INVALID, '缺少必传参数 bank_id');
        }

        [$page, $pageSize] = $this->pageParams($request, 50, 10);

        $userId = $this->currentUserId($request);

        $this->service->assertErrorProneBank($bankId, $userId);

        $paginator = $this->service->errorPronePaginate($userId, $bankId, $page, $pageSize);

        return ApiResponse::paginate($paginator);
    }
}
