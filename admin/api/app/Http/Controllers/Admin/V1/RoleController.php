<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Models\SysRole;
use App\Support\ApiResponse;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 角色管理（docs/04 §五 API-ADM-SYS-001）
 *   API-ADM-030 GET    /roles         列表（含 permission_ids）
 *   API-ADM-031 POST   /roles         新建
 *   API-ADM-032 PUT    /roles/{id}    编辑
 *   API-ADM-033 DELETE /roles/{id}    删除（有管理员在用则拒绝）
 */
class RoleController extends Controller
{
    /** API-ADM-030 角色列表（含权限点） */
    public function index(): JsonResponse
    {
        $roles = SysRole::with('permissions:id,code')->orderBy('sort_order')->get();

        $items = $roles->map(function (SysRole $role) {
            $arr = $role->toArray();
            $arr['permission_ids'] = $role->permissions->pluck('id')->all();

            return $arr;
        })->all();

        return ApiResponse::success(['list' => $items]);
    }

    /** API-ADM-031 新建角色 */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:32'],
            'code'           => ['required', 'string', 'max:32', 'regex:/^[a-z0-9_]+$/'],
            'description'    => ['string', 'max:255'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer'],
        ]);

        if (SysRole::where('code', $data['code'])->exists()) {
            throw new BusinessException(ErrorCode::DATA_EXISTS, '角色编码已存在', null, 422);
        }

        $role = SysRole::create([
            'name'        => $data['name'],
            'code'        => $data['code'],
            'description' => $data['description'] ?? '',
            'is_system'   => SysRole::NOT_SYSTEM,
            'sort_order'  => 99,
            'status'      => SysRole::STATUS_NORMAL,
        ]);

        $role->permissions()->sync($data['permission_ids'] ?? []);

        return ApiResponse::success($role->toArray(), '创建成功');
    }

    /** API-ADM-032 编辑角色 */
    public function update(Request $request, int $id): JsonResponse
    {
        /** @var SysRole|null $role */
        $role = SysRole::find($id);
        if ($role === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '角色不存在', null, 404);
        }

        $data = $request->validate([
            'name'           => ['string', 'max:32'],
            'description'    => ['string', 'max:255'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer'],
        ]);

        if (isset($data['name'])) {
            $role->update(['name' => $data['name']]);
        }
        if (isset($data['description'])) {
            $role->update(['description' => $data['description']]);
        }
        if (array_key_exists('permission_ids', $data)) {
            $role->permissions()->sync($data['permission_ids']);
        }

        return ApiResponse::success($role->toArray(), '更新成功');
    }

    /** API-ADM-033 删除角色（系统预置 / 有管理员在用则拒绝） */
    public function destroy(int $id): JsonResponse
    {
        /** @var SysRole|null $role */
        $role = SysRole::find($id);
        if ($role === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '角色不存在', null, 404);
        }

        if ($role->is_system === SysRole::IS_SYSTEM) {
            throw new BusinessException(ErrorCode::ROLE_SYSTEM_PROTECTED, '系统预置角色不可删除', null, 403);
        }

        if ($role->admins()->count() > 0) {
            throw new BusinessException(ErrorCode::ROLE_IN_USE, '该角色仍有管理员在使用，无法删除', null, 403);
        }

        $role->delete();

        return ApiResponse::ok('删除成功');
    }
}
