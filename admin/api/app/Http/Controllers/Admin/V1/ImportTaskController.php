<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\QuestionImportTask;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AI 导题任务监控（docs/04 §五，监控端）
 *   API-ADM-090 GET /import-tasks 任务列表（status 筛选）
 *
 * 说明：解析执行由主应用 Job 负责，总后台仅做只读监控。
 */
class ImportTaskController extends Controller
{
    /** API-ADM-090 导题任务监控 */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $page = $this->pageParams();

        $query = QuestionImportTask::query();

        if (is_numeric($status) && (int) $status > 0) {
            $query->where('status', (int) $status);
        }

        $paginator = $query->orderByDesc('id')
            ->paginate($page['page_size'], ['*'], 'page', $page['page']);

        return ApiResponse::paginate($paginator);
    }
}
