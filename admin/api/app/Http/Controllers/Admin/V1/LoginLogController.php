<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\SysLoginLog;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 登录日志（docs/04 §五 API-ADM-SYS-002）
 *   API-ADM-051 GET /logs/login 登录日志列表
 */
class LoginLogController extends Controller
{
    /** API-ADM-051 登录日志 */
    public function index(Request $request): JsonResponse
    {
        $page = $this->pageParams();

        $paginator = SysLoginLog::query()
            ->orderByDesc('id')
            ->paginate($page['page_size'], ['*'], 'page', $page['page']);

        return ApiResponse::paginate($paginator);
    }
}
