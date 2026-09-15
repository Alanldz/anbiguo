<?php

use Illuminate\Support\Facades\Route;

/**
 * Web 路由（本应用不提供页面，仅保留健康检查与站点根提示）
 * 所有业务接口见 routes/client.php 与 routes/console_api.php
 */

Route::get('/', function () {
    return response()->json([
        'service' => 'anbiguo-server',
        'docs'    => 'docs/04-API接口规范与登记表.md',
        'domains' => [
            'client'  => '/api/v1',
            'console' => '/console-api/v1',
        ],
    ]);
});
