<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Member;

use App\Http\Controllers\Controller;
use App\Services\Api\MemberService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 客户端会员控制器（套餐列表 + 开通下单，支付留待 PAY 接入）
 * 台账：docs/04-API接口规范与登记表.md §三 API-MBR-001 ~ 002
 */
class MemberController extends Controller
{
    public function __construct(private readonly MemberService $service)
    {
    }

    /** API-MBR-001 会员权益与套餐列表 */
    public function plans(Request $request): JsonResponse
    {
        return ApiResponse::success($this->service->plans());
    }

    /** API-MBR-002 开通会员下单（返回待支付订单，真实支付留待 API-PAY-001/002） */
    public function storeOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'min:1'],
        ]);

        $platform = (string) $request->header('X-Client-Platform', 'mp-weixin');

        return ApiResponse::success(
            $this->service->createOrder($this->currentUserId($request), (int) $data['plan_id'], $platform)
        );
    }
}
