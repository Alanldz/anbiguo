<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\SysPermission;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * 权限管理（docs/04 §五 API-ADM-SYS-001）
 *   API-ADM-034 GET /permissions 权限树（按 parent_id 组树）
 */
class PermissionController extends Controller
{
    /** API-ADM-034 权限树 */
    public function index(): JsonResponse
    {
        $all = SysPermission::orderBy('sort_order')->get();

        $byId = [];
        foreach ($all as $perm) {
            $byId[$perm->id] = $perm->toArray();
            $byId[$perm->id]['children'] = [];
        }

        $tree = [];
        foreach ($all as $perm) {
            $node = &$byId[$perm->id];
            if ((int) $perm->parent_id === 0) {
                $tree[] = &$node;
            } elseif (isset($byId[$perm->parent_id])) {
                $byId[$perm->parent_id]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
            unset($node);
        }

        return ApiResponse::success(['list' => $tree]);
    }
}
