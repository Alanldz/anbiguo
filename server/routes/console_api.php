<?php

use App\Http\Controllers\Console\V1\AuthController;
use Illuminate\Support\Facades\Route;

/**
 * 用户后台接口路由（前缀 /console-api/v1，中间件组 console）
 * 台账：docs/04-API接口规范与登记表.md §四
 *
 * 与客户端的关键差异（docs/06 §二）：
 *   - 守卫为 auth:console，Token 有效期 2 小时且不提供刷新，过期需重新登录
 *   - 登录态与客户端【互不通用】，两个 JWT 密钥不同，跨界使用会直接 10401
 *   - 数据库账号为 app_console，同样没有 sys_ 表权限
 */

// =============================================================================
// 认证模块 · API-CSL-AUTH-*
// =============================================================================
Route::prefix('auth')->name('auth.')->group(function () {
    // API-CSL-AUTH-001 用户后台登录（手机号 + 密码 / 验证码）
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:console-login')
        ->name('login');

    // API-CSL-AUTH-002 退出登录
    Route::post('logout', [AuthController::class, 'logout'])
        ->middleware('auth:console')
        ->name('logout');

    // API-CSL-AUTH-003 获取当前登录用户信息与权限
    Route::get('me', [AuthController::class, 'me'])
        ->middleware('auth:console')
        ->name('me');
});

// =============================================================================
// 待开发接口（已在 04 文档登记编号，实现后取消注释）
// -----------------------------------------------------------------------------
// 题库  API-CSL-BANK-001  GET  /question-banks
//       API-CSL-BANK-002  GET  /question-banks/{id}
//       API-CSL-BANK-003  POST /question-banks/import
// 文件  API-CSL-FIL-001   GET|POST /file-categories
//       API-CSL-FIL-002   POST /file-assets
// 订单  API-CSL-ORD-001   GET  /orders
// 统计  API-CSL-STAT-001  GET  /statistics/overview
// =============================================================================
