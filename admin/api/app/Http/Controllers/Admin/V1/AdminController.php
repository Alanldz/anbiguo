<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Models\SysAdmin;
use App\Models\SysRole;
use App\Support\ApiResponse;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * 管理员管理（docs/04 §五 API-ADM-SYS-001）
 *   API-ADM-020 GET    /admins            列表（含角色）
 *   API-ADM-021 POST   /admins            新建
 *   API-ADM-022 PUT    /admins/{id}       编辑
 *   API-ADM-023 DELETE /admins/{id}       删除（禁止删自己/最后一个启用超管）
 */
class AdminController extends Controller
{
    /** API-ADM-020 管理员列表 */
    public function index(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->query('keyword', ''));
        $page = $this->pageParams();

        $query = SysAdmin::query()->with('roles:id,name,code');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('username', 'like', "%{$keyword}%")
                  ->orWhere('real_name', 'like', "%{$keyword}%");
            });
        }

        $paginator = $query->orderByDesc('id')
            ->paginate($page['page_size'], ['*'], 'page', $page['page']);

        return ApiResponse::paginate($paginator);
    }

    /** API-ADM-021 新建管理员 */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username'   => ['required', 'string', 'max:32'],
            'nickname'   => ['required', 'string', 'max:32'],
            'password'   => ['required', 'string', 'min:6', 'max:100'],
            'role_ids'   => ['array'],
            'role_ids.*' => ['integer'],
            'status'     => ['integer', 'in:1,2'],
        ]);

        if (SysAdmin::where('username', $data['username'])->exists()) {
            throw new BusinessException(ErrorCode::ADMIN_USERNAME_EXISTS, '该登录账号已存在', null, 422);
        }

        $admin = SysAdmin::create([
            'username' => $data['username'],
            'real_name'=> $data['nickname'],
            'password' => Hash::make($data['password']),
            'status'   => $data['status'] ?? SysAdmin::STATUS_NORMAL,
        ]);

        $this->syncRoles($admin, $data['role_ids'] ?? []);

        return ApiResponse::success($admin->load('roles:id,name,code')->toArray(), '创建成功');
    }

    /** API-ADM-022 编辑管理员（password 可选重置） */
    public function update(Request $request, int $id): JsonResponse
    {
        /** @var SysAdmin|null $admin */
        $admin = SysAdmin::find($id);
        if ($admin === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '管理员不存在', null, 404);
        }

        $data = $request->validate([
            'nickname'   => ['string', 'max:32'],
            'password'   => ['nullable', 'string', 'min:6', 'max:100'],
            'role_ids'   => ['array'],
            'role_ids.*' => ['integer'],
            'status'     => ['integer', 'in:1,2'],
        ]);

        $attrs = [];
        if (isset($data['nickname'])) {
            $attrs['real_name'] = $data['nickname'];
        }
        if (isset($data['status'])) {
            $attrs['status'] = $data['status'];
        }
        if (! empty($data['password'])) {
            $attrs['password'] = Hash::make($data['password']);
        }
        if ($attrs !== []) {
            $admin->update($attrs);
        }

        if (array_key_exists('role_ids', $data)) {
            $this->syncRoles($admin, $data['role_ids']);
        }

        return ApiResponse::success($admin->load('roles:id,name,code')->toArray(), '更新成功');
    }

    /** API-ADM-023 删除管理员 */
    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var SysAdmin $current */
        $current = $request->user();

        if ($current->id === $id) {
            throw new BusinessException(ErrorCode::ADMIN_DELETE_FORBIDDEN, '禁止删除自己', null, 403);
        }

        /** @var SysAdmin|null $admin */
        $admin = SysAdmin::find($id);
        if ($admin === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '管理员不存在', null, 404);
        }

        // 禁止删除最后一个启用状态的超级管理员
        if ($admin->is_super === SysAdmin::IS_SUPER
            && $admin->status === SysAdmin::STATUS_NORMAL) {
            $enabledSuper = SysAdmin::where('is_super', SysAdmin::IS_SUPER)
                ->where('status', SysAdmin::STATUS_NORMAL)
                ->count();
            if ($enabledSuper <= 1) {
                throw new BusinessException(ErrorCode::ADMIN_DELETE_FORBIDDEN, '禁止删除最后一个启用状态的超级管理员', null, 403);
            }
        }

        $admin->delete();

        return ApiResponse::ok('删除成功');
    }

    /** 同步管理员-角色关联 */
    private function syncRoles(SysAdmin $admin, array $roleIds): void
    {
        $validIds = SysRole::whereIn('id', $roleIds)->pluck('id')->all();
        $admin->roles()->sync($validIds);
    }
}
