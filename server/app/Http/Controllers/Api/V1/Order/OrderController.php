<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Order;

use App\Http\Controllers\Controller;
use App\Services\Api\OrderService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 客户端订单控制器（我的订单列表，仅本人）
 * 台账：docs/04-API接口规范与登记表.md §三 API-ORD-001
 */
class OrderController extends Controller
{
    public function __construct(private readonly OrderService $service)
    {
    }

    /** API-ORD-001 我的订单列表（分页，可选状态过滤） */
    public function index(Request $request): JsonResponse
    {
        [$page, $pageSize] = $this->pageParams($request);
        $status = $request->filled('status') ? (int) $request->input('status') : null;

        return ApiResponse::paginate(
            $this->service->list($this->currentUserId($request), $status, $page, $pageSize)
        );
    }
}
