<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Controller;
use App\Services\Api\NotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 消息通知中心控制器
 * 台账：docs/04-API接口规范与登记表.md §二 API-MSG-001 ~ 004
 */
class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $service)
    {
    }

    /** API-MSG-001 通知列表 */
    public function index(Request $request): JsonResponse
    {
        [$page, $pageSize] = $this->pageParams($request);

        $paginator = $this->service->paginate(
            $this->currentUserId($request),
            $request->only(['type', 'is_read']),
            $page,
            $pageSize
        );

        return ApiResponse::paginate($paginator);
    }

    /** API-MSG-002 未读通知数量 */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->service->unreadCount($this->currentUserId($request));

        return ApiResponse::success(['count' => $count]);
    }

    /** API-MSG-003 标记单条已读（幂等） */
    public function markRead(Request $request, int $id): JsonResponse
    {
        $this->service->markRead($id, $this->currentUserId($request));

        return ApiResponse::success(['marked' => true]);
    }

    /** API-MSG-004 全部已读 */
    public function readAll(Request $request): JsonResponse
    {
        $marked = $this->service->readAll($this->currentUserId($request));

        return ApiResponse::success(['marked' => $marked]);
    }
}
