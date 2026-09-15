<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Services\Admin\OrderService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 订单管理 / 退款（docs/04 §五 API-ADM-102）
 *   API-ADM-102 GET  /orders              订单列表（分页 + 筛选）
 *   API-ADM-102 POST /orders/{id}/refund  退款（仅已支付，status 置 3，reason 追加 remark）
 *
 * 台账：docs/04 §五 API-ADM-102
 *
 * ⚠️ 真实微信退款调用不在本次范围（商户号未接入），资金原路退回待微信支付接入后执行。
 */
class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService)
    {
    }

    /** API-ADM-102 订单列表 */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'page'       => $request->query('page'),
            'page_size'  => $request->query('page_size'),
            'order_no'   => $request->query('order_no'),
            'keyword'    => $request->query('keyword'),
            'order_type' => $request->query('order_type'),
            'status'     => $request->query('status'),
            'date_from'  => $request->query('date_from'),
            'date_to'    => $request->query('date_to'),
        ];

        $paginator = $this->orderService->list($filters);

        $list = collect($paginator->items())->map(fn ($o) => $this->orderService->toRow($o))->all();

        return ApiResponse::success([
            'list' => $list,
            'pagination' => [
                'page'        => $paginator->currentPage(),
                'page_size'   => $paginator->perPage(),
                'total'       => $paginator->total(),
                'total_pages' => $paginator->lastPage(),
            ],
        ]);
    }

    /** API-ADM-102 退款 */
    public function refund(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        /** @var \App\Models\SysAdmin $admin */
        $admin = $request->user();
        $row = $this->orderService->refund($id, $data['reason'], (int) $admin->getAuthIdentifier());

        return ApiResponse::success($row, '退款状态已记录，资金原路退回待微信支付接入后执行');
    }
}
