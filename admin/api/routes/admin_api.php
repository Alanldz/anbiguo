<?php

declare(strict_types=1);

/**
 * 总后台 API · 独立应用 · 前缀 /admin-api/v1（docs/04 §五）
 *
 * 隔离约束（docs/06 §二）：
 *   - 与主应用 server/ 完全独立，不共享代码/进程/域名/数据库账号
 *   - 鉴权守卫 auth:admin（scp=admin，独立密钥 JWT_SECRET_ADMIN）
 *   - 数据库账号 app_admin（唯一能读写 sys_* 表）
 *
 * 台账编号见各路由 docblock（API-ADM-0xx），与主应用 docs/04 §五登记一致。
 */

use App\Http\Controllers\Admin\V1\AdminController;
use App\Http\Controllers\Admin\V1\AuthController;
use App\Http\Controllers\Admin\V1\BankController;
use App\Http\Controllers\Admin\V1\BannerController;
use App\Http\Controllers\Admin\V1\ConfigController;
use App\Http\Controllers\Admin\V1\DashboardController;
use App\Http\Controllers\Admin\V1\ImportTaskController;
use App\Http\Controllers\Admin\V1\LoginLogController;
use App\Http\Controllers\Admin\V1\OperationLogController;
use App\Http\Controllers\Admin\V1\PermissionController;
use App\Http\Controllers\Admin\V1\RoleController;
use App\Http\Controllers\Admin\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 认证（无鉴权）
|--------------------------------------------------------------------------
*/

// API-ADM-001 登录
Route::post('auth/login', [AuthController::class, 'login']);

// 以下全部需要 auth:admin 守卫（scp=admin），且登录后校验管理员状态
Route::middleware(['auth:admin', 'admin.active'])->group(function () {

    // API-ADM-002 登出
    Route::post('auth/logout', [AuthController::class, 'logout']);
    // API-ADM-003 当前管理员
    Route::get('auth/me', [AuthController::class, 'me']);

    /*
    |--------------------------------------------------------------------------
    | 仪表盘
    |--------------------------------------------------------------------------
    */

    // API-ADM-010 仪表盘统计
    Route::get('dashboard/summary', [DashboardController::class, 'summary']);

    /*
    |--------------------------------------------------------------------------
    | 管理员与角色权限
    |--------------------------------------------------------------------------
    */

    // API-ADM-020 管理员列表
    Route::get('admins', [AdminController::class, 'index']);
    // API-ADM-021 新建管理员
    Route::post('admins', [AdminController::class, 'store'])->middleware('op.log:admin,create');
    // API-ADM-022 编辑管理员
    Route::put('admins/{id}', [AdminController::class, 'update'])->middleware('op.log:admin,update');
    // API-ADM-023 删除管理员
    Route::delete('admins/{id}', [AdminController::class, 'destroy'])->middleware('op.log:admin,delete');

    // API-ADM-030 角色列表
    Route::get('roles', [RoleController::class, 'index']);
    // API-ADM-031 新建角色
    Route::post('roles', [RoleController::class, 'store'])->middleware('op.log:role,create');
    // API-ADM-032 编辑角色
    Route::put('roles/{id}', [RoleController::class, 'update'])->middleware('op.log:role,update');
    // API-ADM-033 删除角色
    Route::delete('roles/{id}', [RoleController::class, 'destroy'])->middleware('op.log:role,delete');

    // API-ADM-034 权限树
    Route::get('permissions', [PermissionController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | 配置中心
    |--------------------------------------------------------------------------
    */

    // API-ADM-040 配置列表
    Route::get('configs', [ConfigController::class, 'index']);
    // API-ADM-041 修改配置
    Route::put('configs/{id}', [ConfigController::class, 'update'])->middleware('op.log:config,update');

    /*
    |--------------------------------------------------------------------------
    | 日志
    |--------------------------------------------------------------------------
    */

    // API-ADM-050 操作日志
    Route::get('logs/operation', [OperationLogController::class, 'index']);
    // API-ADM-051 登录日志
    Route::get('logs/login', [LoginLogController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | 用户管理
    |--------------------------------------------------------------------------
    */

    // API-ADM-060 用户列表
    Route::get('users', [UserController::class, 'index']);
    // API-ADM-061 启用/禁用用户
    Route::put('users/{id}/status', [UserController::class, 'updateStatus'])->middleware('op.log:user,ban');

    /*
    |--------------------------------------------------------------------------
    | 题库与审核
    |--------------------------------------------------------------------------
    */

    // API-ADM-070 题库列表
    Route::get('banks', [BankController::class, 'index']);
    // API-ADM-071 内容审核
    Route::put('banks/{id}/audit', [BankController::class, 'audit'])->middleware('op.log:bank,audit');
    // API-ADM-072 上架/隐藏
    Route::put('banks/{id}/status', [BankController::class, 'updateStatus'])->middleware('op.log:bank,update');

    /*
    |--------------------------------------------------------------------------
    | 运营管理
    |--------------------------------------------------------------------------
    */

    // API-ADM-080 轮播列表
    Route::get('banners', [BannerController::class, 'index']);
    // API-ADM-081 新建轮播
    Route::post('banners', [BannerController::class, 'store'])->middleware('op.log:banner,create');
    // API-ADM-082 编辑轮播
    Route::put('banners/{id}', [BannerController::class, 'update'])->middleware('op.log:banner,update');
    // API-ADM-083 删除轮播
    Route::delete('banners/{id}', [BannerController::class, 'destroy'])->middleware('op.log:banner,delete');

    /*
    |--------------------------------------------------------------------------
    | AI 导题任务监控
    |--------------------------------------------------------------------------
    */

    // API-ADM-090 导题任务监控
    Route::get('import-tasks', [ImportTaskController::class, 'index']);
});
