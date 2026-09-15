<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * 控制器基类
 *
 * 约定（docs/02-代码命名规范.md §五 禁止事项）：
 *   - 控制器里不写 SQL、不写业务规则，只做「取参 → 调用 Service → 返回响应」
 *   - 所有返回值必须经 App\Support\ApiResponse
 */
abstract class Controller
{
    /** 当前登录用户（客户端 / 用户后台守卫均可解析） */
    protected function currentUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    /** 当前登录用户 ID */
    protected function currentUserId(Request $request): int
    {
        return (int) $this->currentUser($request)->id;
    }

    /** 分页参数：统一 page / page_size，并做上限保护 */
    protected function pageParams(Request $request): array
    {
        $page = max((int) $request->input('page', 1), 1);
        $max = (int) config('anbiguo.page.size_max', 100);
        $size = (int) $request->input('page_size', config('anbiguo.page.size_default', 20));
        $size = max(1, min($size, $max));

        return [$page, $size];
    }
}
