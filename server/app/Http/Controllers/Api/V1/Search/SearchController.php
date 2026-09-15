<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Search;

use App\Http\Controllers\Controller;
use App\Services\Api\QuestionSearchService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 客户端搜索控制器（题库内试题搜索，不依赖 AI）
 * 台账：docs/04-API接口规范与登记表.md §三 API-SRC-001
 */
class SearchController extends Controller
{
    public function __construct(private readonly QuestionSearchService $service)
    {
    }

    /** API-SRC-001 题库内关键词搜索试题 */
    public function questions(Request $request): JsonResponse
    {
        [$page, $pageSize] = $this->pageParams($request);
        $params = $request->only(['keyword', 'bank_id', 'type']);

        return ApiResponse::paginate(
            $this->service->search($this->currentUserId($request), $params, $page, $pageSize)
        );
    }
}
