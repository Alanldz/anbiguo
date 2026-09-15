<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 用户管理（docs/04 §五 API-ADM-USR-001）
 *   API-ADM-060 GET  /users          用户列表（keyword/status）
 *   API-ADM-061 PUT  /users/{id}/status 启用/禁用（1|2）
 */
class UserController extends Controller
{
    /** API-ADM-060 用户列表 */
    public function index(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->query('keyword', ''));
        $status = $request->query('status');
        $page = $this->pageParams();

        $query = User::query();

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('mobile', 'like', "%{$keyword}%")
                  ->orWhere('uid', 'like', "%{$keyword}%");
            });
        }
        if (is_numeric($status) && (int) $status > 0) {
            $query->where('status', (int) $status);
        }

        $paginator = $query->orderByDesc('id')
            ->paginate($page['page_size'], ['*'], 'page', $page['page']);

        return ApiResponse::paginate($paginator);
    }

    /** API-ADM-061 启用/禁用用户 */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        /** @var User|null $user */
        $user = User::find($id);
        if ($user === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '用户不存在', null, 404);
        }

        $data = $request->validate([
            'status' => ['required', 'integer', 'in:1,2'],
        ]);

        $user->update(['status' => $data['status']]);

        return ApiResponse::success($user->toArray(), '操作成功');
    }
}
