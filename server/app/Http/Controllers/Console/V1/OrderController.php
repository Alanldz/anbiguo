<?php

declare(strict_types=1);

namespace App\Http\Controllers\Console\V1;

use App\Http\Controllers\Controller;
use App\Services\Console\ConsoleOrderService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 订单与会员控制器
 * 台账：docs/04-API接口规范与登记表.md §四 API-CSL-ORD-001 ~ 004
 */
class OrderController extends Controller
{
    public function __construct(private readonly ConsoleOrderService $orderService)
    {
    }

    /** API-CSL-ORD-001 我的订单 */
    public function index(Request $request): JsonResponse
    {
        [$page, $pageSize] = $this->pageParams($request);

        $paginator = $this->orderService->paginateOrders(
            $this->currentUserId($request),
            $request->only(['status', 'order_type']),
            $page,
            $pageSize
        );

        return ApiResponse::paginate($paginator);
    }

    /** API-CSL-ORD-002 订单详情 */
    public function show(Request $request, int $id): JsonResponse
    {
        return ApiResponse::success($this->orderService->orderDetail($id, $this->currentUserId($request)));
    }

    /** API-CSL-ORD-003 我的会员 */
    public function member(Request $request): JsonResponse
    {
        return ApiResponse::success($this->orderService->member($this->currentUserId($request)));
    }

    /** API-CSL-ORD-004 会员套餐 */
    public function plans(Request $request): JsonResponse
    {
        return ApiResponse::success($this->orderService->plans());
    }
}
