<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * 总后台控制器基类
 *
 * 提供统一分页参数解析：page（默认 1）/ page_size（默认 20，最大 100）。
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    /** 分页参数：page 默认 1，page_size 默认 20、上限 100（docs/04） */
    protected function pageParams(): array
    {
        $page = (int) request()->query('page', 1);
        $pageSize = (int) request()->query('page_size', 20);

        return [
            'page'      => max(1, $page),
            'page_size' => max(1, min(100, $pageSize)),
        ];
    }
}
