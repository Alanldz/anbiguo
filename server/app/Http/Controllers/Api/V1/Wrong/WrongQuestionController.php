<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Wrong;

use App\Http\Controllers\Controller;
use App\Services\Api\WrongQuestionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 客户端错题控制器
 * 台账：docs/04-API接口规范与登记表.md §三 API-WRG-001 ~ 002
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
}
