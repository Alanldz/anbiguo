<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Services\Admin\FeedbackService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 意见反馈处理（docs/04 §五 API-ADM-104）
 *   API-ADM-104 GET  /feedbacks             反馈列表（分页 + 筛选，images 解析为数组）
 *   API-ADM-104 PUT  /feedbacks/{id}/handle 处理（status 1|2 / reply / handler=当前管理员）
 *
 * 台账：docs/04 §五 API-ADM-104
 */
class FeedbackController extends Controller
{
    public function __construct(private readonly FeedbackService $feedbackService)
    {
    }

    /** API-ADM-104 反馈列表 */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'page'      => $request->query('page'),
            'page_size' => $request->query('page_size'),
            'status'    => $request->query('status'),
            'type'      => $request->query('type'),
            'keyword'   => $request->query('keyword'),
        ];

        $paginator = $this->feedbackService->list($filters);

        $list = collect($paginator->items())->map(fn ($f) => $this->feedbackService->toRow($f))->all();

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

    /** API-ADM-104 处理反馈 */
    public function handle(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'integer', 'in:1,2'],
            'reply'  => ['nullable', 'string', 'max:500'],
        ]);

        /** @var \App\Models\SysAdmin $admin */
        $admin = $request->user();
        $row = $this->feedbackService->handle(
            $id,
            (int) $data['status'],
            $data['reply'] ?? null,
            (int) $admin->getAuthIdentifier()
        );

        return ApiResponse::success($row, '处理成功');
    }
}
