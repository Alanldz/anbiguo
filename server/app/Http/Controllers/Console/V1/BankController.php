<?php

declare(strict_types=1);

namespace App\Http\Controllers\Console\V1;

use App\Http\Controllers\Controller;
use App\Services\Console\ConsoleBankService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 题库控制器
 * 台账：docs/04-API接口规范与登记表.md §四 API-CSL-BANK-001 ~ 007
 */
class BankController extends Controller
{
    public function __construct(private readonly ConsoleBankService $bankService)
    {
    }

    /** API-CSL-BANK-001 题库列表 */
    public function index(Request $request): JsonResponse
    {
        [$page, $pageSize] = $this->pageParams($request);

        $paginator = $this->bankService->paginateMine(
            $this->currentUserId($request),
            $request->only(['keyword', 'category_id', 'source_type', 'status']),
            $page,
            $pageSize
        );

        return ApiResponse::paginate($paginator);
    }

    /** API-CSL-BANK-003 新建题库 */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:120'],
            'subtitle'     => ['nullable', 'string', 'max:255'],
            'category_id'  => ['nullable', 'integer', 'min:1'],
            'cover'        => ['nullable', 'string', 'max:255'],
            'charge_type'  => ['nullable', 'integer', Rule::in([1, 2, 3])],
        ], [
            'title.required' => '请输入题库名称',
        ]);

        $result = $this->bankService->create($this->currentUser($request), $data);

        return ApiResponse::success($result, '题库创建成功');
    }

    /** API-CSL-BANK-002 题库详情 */
    public function show(Request $request, int $id): JsonResponse
    {
        return ApiResponse::success($this->bankService->detail($id, $this->currentUserId($request)));
    }

    /** API-CSL-BANK-004 更新 / 重命名题库 */
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'title'        => ['nullable', 'string', 'max:120'],
            'subtitle'     => ['nullable', 'string', 'max:255'],
            'category_id'  => ['nullable', 'integer', 'min:1'],
            'cover'        => ['nullable', 'string', 'max:255'],
            'charge_type'  => ['nullable', 'integer', Rule::in([1, 2, 3])],
        ]);

        $this->bankService->update($id, $this->currentUserId($request), $data);

        return ApiResponse::ok('保存成功');
    }

    /** API-CSL-BANK-005 删除题库 */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->bankService->delete($id, $this->currentUserId($request));

        return ApiResponse::ok('题库已删除');
    }

    /** API-CSL-BANK-006 导出题库 */
    public function export(Request $request, int $id): JsonResponse
    {
        return ApiResponse::success($this->bankService->export($id, $this->currentUserId($request)));
    }

    /** API-CSL-BANK-007 题库分类树（下拉用） */
    public function categories(Request $request): JsonResponse
    {
        return ApiResponse::success($this->bankService->categoryTree());
    }
}
