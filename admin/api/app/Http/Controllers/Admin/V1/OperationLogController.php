<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\SysLog;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 操作日志（docs/04 §五 API-ADM-SYS-002）
 *   API-ADM-050 GET /logs/operation 操作日志列表
 */
class OperationLogController extends Controller
{
    /** API-ADM-050 操作日志 */
    public function index(Request $request): JsonResponse
    {
        $adminId = $request->query('admin_id');
        $action = trim((string) $request->query('action', ''));
        $page = $this->pageParams();

        $query = SysLog::query();

        if (is_numeric($adminId) && (int) $adminId > 0) {
            $query->where('admin_id', (int) $adminId);
        }
        if ($action !== '') {
            $query->where('action', $action);
        }

        $paginator = $query->orderByDesc('id')
            ->paginate($page['page_size'], ['*'], 'page', $page['page']);

        return ApiResponse::paginate($paginator);
    }
}
