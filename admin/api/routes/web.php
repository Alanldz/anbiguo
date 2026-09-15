<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// 总后台为纯 API 应用，无 Web 页面路由。
// /admin-api/v1/* 在 routes/admin_api.php 注册。
Route::get('/', function () {
    return response()->json(['app' => config('app.name'), 'message' => '识途刷题总后台 API']);
});
