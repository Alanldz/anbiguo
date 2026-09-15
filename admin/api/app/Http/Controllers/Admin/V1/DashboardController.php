<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * 数据看板（docs/04 §五 API-ADM-SYS-003）
 *   API-ADM-010 GET /dashboard/summary 统计概览
 */
class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    /** API-ADM-010 仪表盘统计 */
    public function summary(): JsonResponse
    {
        return ApiResponse::success($this->dashboardService->summary());
    }
}
