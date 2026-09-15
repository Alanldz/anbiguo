<?php

declare(strict_types=1);

namespace App\Http\Controllers\Console\V1;

use App\Http\Controllers\Controller;
use App\Services\Console\ConsoleStatisticsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 学习概览控制器
 * 台账：docs/04-API接口规范与登记表.md §四 API-CSL-STAT-001
 */
class StatisticsController extends Controller
{
    public function __construct(private readonly ConsoleStatisticsService $service)
    {
    }

    /** API-CSL-STAT-001 学习概览汇总 */
    public function overview(Request $request): JsonResponse
    {
        return ApiResponse::success($this->service->overview($this->currentUserId($request)));
    }
}
