<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Services\Admin\CategoryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 分类管理（docs/04 §五 API-ADM-100）
 *   API-ADM-100 GET    /categories          列表（?type=bank|file，含 parent_name 组装）
 *   API-ADM-100 POST   /categories          新建（bank 全局唯一 / file 系统预置唯一）
 *   API-ADM-100 PUT    /categories/{id}     编辑（code 不可改，?type=bank|file）
 *   API-ADM-100 DELETE /categories/{id}     删除（软删；有子分类/在用禁止，?type=bank|file）
 *
 * 台账：docs/04 §五 API-ADM-100
 *
 * 说明：写操作（PUT/DELETE）通过 ?type= 区分 bank/file 表（GET 同），
 *   因两类分类在同一接口下，type 必填；默认值 bank。
 */
class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService)
    {
    }

    /** API-ADM-100 分类列表 */
    public function index(Request $request): JsonResponse
    {
        $type = (string) $request->query('type', CategoryService::TYPE_BANK);
        $list = $this->categoryService->list($type);

        return ApiResponse::success([
            'list'  => $list,
            'total' => count($list),
        ]);
    }

    /** API-ADM-100 新建分类 */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type'       => ['required', 'string', 'in:bank,file'],
            'parent_id'  => ['integer'],
            'name'       => ['required', 'string', 'max:32'],
            'code'       => ['required', 'string', 'max:32'],
            'icon'       => ['string', 'max:255'],
            'sort_order' => ['integer'],
            'status'     => ['integer', 'in:1,2'],
        ]);

        return ApiResponse::success($this->categoryService->create($data), '创建成功', 201);
    }

    /** API-ADM-100 编辑分类（code 不可改） */
    public function update(Request $request, int $id): JsonResponse
    {
        $type = (string) $request->query('type', CategoryService::TYPE_BANK);
        $data = $request->validate([
            'name'       => ['string', 'max:32'],
            'icon'       => ['string', 'max:255'],
            'sort_order' => ['integer'],
            'status'     => ['integer', 'in:1,2'],
        ]);

        return ApiResponse::success($this->categoryService->update($id, $type, $data), '更新成功');
    }

    /** API-ADM-100 删除分类 */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $type = (string) $request->query('type', CategoryService::TYPE_BANK);
        $this->categoryService->delete($id, $type);

        return ApiResponse::ok('删除成功');
    }
}
