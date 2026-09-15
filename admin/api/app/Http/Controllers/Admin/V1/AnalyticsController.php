<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Services\Admin\AnalyticsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * 埋点分析（docs/04 §五 API-ADM-106）
 *   权限码：sys:statistics:view（SystemInitSeeder「数据看板 → 查看看板」）
 */
class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $service)
    {
    }

    /** API-ADM-106 埋点分析汇总 */
    public function summary(): JsonResponse
    {
        return ApiResponse::success($this->service->summary());
    }
}
