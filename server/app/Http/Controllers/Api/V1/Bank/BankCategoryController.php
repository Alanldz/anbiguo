<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Bank;

use App\Http\Controllers\Controller;
use App\Models\BankCategory;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * 题库分类控制器
 * 台账：docs/04-API接口规范与登记表.md §三 API-BANK-001
 */
class BankCategoryController extends Controller
{
    /**
     * API-BANK-001 题库分类列表
     *
     * 目前只维护一级分类（参考软件的题库市场使用扁平分类）；
     * 后续若引入二级分类，本接口保持返回树形结构即可，前端无需改动。
     */
    public function index(): JsonResponse
    {
        $categories = BankCategory::query()
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->get(['id', 'parent_id', 'name', 'code', 'icon', 'level', 'question_bank_count']);

        $list = $categories->map(fn (BankCategory $category) => [
            'id'                  => $category->id,
            'parent_id'           => (int) $category->parent_id,
            'name'                => $category->name,
            'code'                => $category->code,
            'icon'                => $category->icon,
            'level'               => (int) $category->level,
            'question_bank_count' => (int) $category->question_bank_count,
        ])->all();

        return ApiResponse::success(['list' => $list]);
    }
}
