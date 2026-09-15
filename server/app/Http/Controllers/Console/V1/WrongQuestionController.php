<?php

declare(strict_types=1);

namespace App\Http\Controllers\Console\V1;

use App\Http\Controllers\Controller;
use App\Services\Console\ConsoleWrongQuestionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 错题控制器
 * 台账：docs/04-API接口规范与登记表.md §四 API-CSL-WRG-001 ~ 003
 */
class WrongQuestionController extends Controller
{
    public function __construct(private readonly ConsoleWrongQuestionService $wrongService)
    {
    }

    /** API-CSL-WRG-001 错题列表 */
    public function index(Request $request): JsonResponse
    {
        [$page, $pageSize] = $this->pageParams($request);

        $paginator = $this->wrongService->paginate(
            $this->currentUserId($request),
            $request->only(['bank_id', 'keyword']),
            $page,
            $pageSize
        );

        return ApiResponse::paginate($paginator);
    }

    /** API-CSL-WRG-002 移除单条 */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->wrongService->remove($id, $this->currentUserId($request));

        return ApiResponse::ok('已从错题本移除');
    }

    /** API-CSL-WRG-003 批量移除 */
    public function batchRemove(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'min:1'],
        ], [
            'ids.required' => '请选择要移除的错题',
        ]);

        $this->wrongService->batchRemove($data['ids'], $this->currentUserId($request));

        return ApiResponse::ok('已移除所选错题');
    }
}
