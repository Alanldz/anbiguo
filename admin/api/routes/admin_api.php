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
use App\Http\Controllers\Admin\V1\CategoryController;
use App\Http\Controllers\Admin\V1\ConfigController;
use App\Http\Controllers\Admin\V1\FeedbackController;
use App\Http\Controllers\Admin\V1\FileController;
use App\Http\Controllers\Admin\V1\MemberPlanController;
use App\Http\Controllers\Admin\V1\OrderController;
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

    /*
    |--------------------------------------------------------------------------
    | 分类管理（bank_categories / file_categories 系统预置）
    |--------------------------------------------------------------------------
    */

    // API-ADM-100 分类列表（?type=bank|file）
    Route::get('categories', [CategoryController::class, 'index']);
    // API-ADM-100 新建分类
    Route::post('categories', [CategoryController::class, 'store'])->middleware('op.log:category,create');
    // API-ADM-100 编辑分类（?type=bank|file）
    Route::put('categories/{id}', [CategoryController::class, 'update'])->middleware('op.log:category,update');
    // API-ADM-100 删除分类（?type=bank|file）
    Route::delete('categories/{id}', [CategoryController::class, 'destroy'])->middleware('op.log:category,delete');

    /*
    |--------------------------------------------------------------------------
    | 配置连通测试
    |--------------------------------------------------------------------------
    */

    // API-ADM-101 测试配置连通性（sys:config:test）
    Route::post('configs/{id}/test', [ConfigController::class, 'test'])->middleware('op.log:config,test');

    /*
    |--------------------------------------------------------------------------
    | 订单管理 / 退款
    |--------------------------------------------------------------------------
    */

    // API-ADM-102 订单列表
    Route::get('orders', [OrderController::class, 'index']);
    // API-ADM-102 订单退款（仅已支付，资金原路退回待微信支付接入）
    Route::post('orders/{id}/refund', [OrderController::class, 'refund'])->middleware('op.log:order,refund');

    /*
    |--------------------------------------------------------------------------
    | 文件资源管理
    |--------------------------------------------------------------------------
    */

    // API-ADM-103 文件列表
    Route::get('files', [FileController::class, 'index']);
    // API-ADM-103 删除文件（仅软删记录，不调 OSS）
    Route::delete('files/{id}', [FileController::class, 'destroy'])->middleware('op.log:file,delete');

    /*
    |--------------------------------------------------------------------------
    | 意见反馈处理
    |--------------------------------------------------------------------------
    */

    // API-ADM-104 反馈列表
    Route::get('feedbacks', [FeedbackController::class, 'index']);
    // API-ADM-104 处理反馈
    Route::put('feedbacks/{id}/handle', [FeedbackController::class, 'handle'])->middleware('op.log:feedback,handle');

    /*
    |--------------------------------------------------------------------------
    | 会员套餐配置
    |--------------------------------------------------------------------------
    */

    // API-ADM-105 套餐全量列表
    Route::get('member-plans', [MemberPlanController::class, 'index']);
    // API-ADM-105 编辑套餐
    Route::put('member-plans/{id}', [MemberPlanController::class, 'update'])->middleware('op.log:plan,update');
});
