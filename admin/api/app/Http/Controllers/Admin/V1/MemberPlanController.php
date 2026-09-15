<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Services\Admin\MemberPlanService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 会员套餐配置（docs/04 §五 API-ADM-105）
 *   API-ADM-105 GET  /member-plans       全量列表（按 sort_order 升序，benefits 解析为数组）
 *   API-ADM-105 PUT  /member-plans/{id}  编辑（任意子集，benefits 数组后端 json 编码）
 *
 * 台账：docs/04 §五 API-ADM-105
 */
class MemberPlanController extends Controller
{
    public function __construct(private readonly MemberPlanService $memberPlanService)
    {
    }

    /** API-ADM-105 套餐全量列表 */
    public function index(): JsonResponse
    {
        return ApiResponse::success([
            'list' => $this->memberPlanService->listAll(),
        ]);
    }

    /** API-ADM-105 编辑套餐 */
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name'           => ['string', 'max:64'],
            'level'          => ['integer', 'min:1', 'max:4'],
            'duration_days'  => ['integer', 'min:0'],
            'price_amount'   => ['numeric', 'min:0'],
            'origin_amount'  => ['numeric', 'min:0'],
            'description'    => ['string', 'max:255'],
            'benefits'       => ['array'],
            'benefits.*'     => ['string', 'max:200'],
            'ai_import_quota'=> ['integer', 'min:0'],
            'is_recommend'   => ['integer', 'in:0,1'],
            'sort_order'     => ['integer', 'min:0'],
            'status'         => ['integer', 'in:1,2'],
        ]);

        return ApiResponse::success($this->memberPlanService->update($id, $data), '更新成功');
    }
}
