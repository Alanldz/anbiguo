<?php

use App\Http\Controllers\Console\V1\AccountController;
use App\Http\Controllers\Console\V1\AuthController;
use App\Http\Controllers\Console\V1\BankController;
use App\Http\Controllers\Console\V1\ChapterController;
use App\Http\Controllers\Console\V1\ExamRecordController;
use App\Http\Controllers\Console\V1\FileAssetController;
use App\Http\Controllers\Console\V1\FileCategoryController;
use App\Http\Controllers\Console\V1\ImportTaskController;
use App\Http\Controllers\Console\V1\OrderController;
use App\Http\Controllers\Console\V1\QuestionController;
use App\Http\Controllers\Console\V1\StatisticsController;
use App\Http\Controllers\Console\V1\WrongQuestionController;
use Illuminate\Support\Facades\Route;

/**
 * 用户后台接口路由（前缀 /console-api/v1，中间件组 console）
 * 台账：docs/04-API接口规范与登记表.md §四
 *
 * 与客户端的关键差异（docs/06 §二）：
 *   - 守卫为 auth:console，Token 有效期 2 小时且不提供刷新，过期需重新登录
 *   - 登录态与客户端【互不通用】，两个 JWT 密钥不同，跨界使用会直接 10401
 *   - 数据库账号为 app_console，同样没有 sys_ 表权限
 *
 * 路由分组：
 *   - 认证模块：登录/登出/当前用户/短信验证码（sms-code 免登录）
 *   - 受保护模块：统一挂 ['auth:console', 'console.user']
 *   - 路径参数统一 ->whereNumber('id'|'bankId')
 *
 * 命名前缀已由 bootstrap/app.php 设为 console.，文件内仅写 ->name('...')。
 */

// =============================================================================
// 认证模块 · API-CSL-AUTH-*
// =============================================================================
Route::prefix('auth')->name('auth.')->group(function () {
    // API-CSL-AUTH-001 用户后台登录（手机号 + 密码 / 验证码）
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:console-login')
        ->name('login');

    // API-CSL-AUTH-004 发送短信验证码（免登录；login/bind 场景）
    Route::post('sms-code', [AuthController::class, 'smsCode'])
        ->middleware('throttle:console-login')
        ->name('sms-code');

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
// 受保护模块（需登录）· API-CSL-STAT / BANK / CHP / QST / IMP / FIL / WRG / EXM / ORD / ACC
// =============================================================================
Route::middleware(['auth:console', 'console.user'])->group(function () {
    // -------------------------------------------------------------------------
    // 学习概览 · API-CSL-STAT-001
    // -------------------------------------------------------------------------
    // API-CSL-STAT-001 学习概览汇总
    Route::get('statistics/overview', [StatisticsController::class, 'overview'])
        ->name('statistics.overview');

    // -------------------------------------------------------------------------
    // 我的题库 · API-CSL-BANK-*
    // -------------------------------------------------------------------------
    // API-CSL-BANK-001 题库列表
    Route::get('question-banks', [BankController::class, 'index'])
        ->name('question-banks.index');

    // API-CSL-BANK-003 新建题库
    Route::post('question-banks', [BankController::class, 'store'])
        ->name('question-banks.store');

    // API-CSL-BANK-002 题库详情
    Route::get('question-banks/{id}', [BankController::class, 'show'])
        ->whereNumber('id')->name('question-banks.show');

    // API-CSL-BANK-004 更新 / 重命名题库
    Route::put('question-banks/{id}', [BankController::class, 'update'])
        ->whereNumber('id')->name('question-banks.update');

    // API-CSL-BANK-005 删除题库（级联软删题目与章节）
    Route::delete('question-banks/{id}', [BankController::class, 'destroy'])
        ->whereNumber('id')->name('question-banks.destroy');

    // API-CSL-BANK-006 导出题库
    Route::get('question-banks/{id}/export', [BankController::class, 'export'])
        ->whereNumber('id')->name('question-banks.export');

    // API-CSL-BANK-007 题库分类树（下拉用）
    Route::get('bank-categories', [BankController::class, 'categories'])
        ->name('bank-categories.index');

    // -------------------------------------------------------------------------
    // 章节 · API-CSL-CHP-*
    // -------------------------------------------------------------------------
    // API-CSL-CHP-001 章节列表（扁平数组）
    Route::get('question-banks/{bankId}/chapters', [ChapterController::class, 'index'])
        ->whereNumber('bankId')->name('chapters.index');

    // API-CSL-CHP-002 新建章节
    Route::post('question-banks/{bankId}/chapters', [ChapterController::class, 'store'])
        ->whereNumber('bankId')->name('chapters.store');

    // API-CSL-CHP-003 更新章节
    Route::put('chapters/{id}', [ChapterController::class, 'update'])
        ->whereNumber('id')->name('chapters.update');

    // API-CSL-CHP-004 删除章节（题目 chapter_id 置 0）
    Route::delete('chapters/{id}', [ChapterController::class, 'destroy'])
        ->whereNumber('id')->name('chapters.destroy');

    // -------------------------------------------------------------------------
    // 题目管理 · API-CSL-QST-*
    // -------------------------------------------------------------------------
    // API-CSL-QST-001 题目列表
    Route::get('question-banks/{bankId}/questions', [QuestionController::class, 'index'])
        ->whereNumber('bankId')->name('questions.index');

    // API-CSL-QST-003 新增题目
    Route::post('question-banks/{bankId}/questions', [QuestionController::class, 'store'])
        ->whereNumber('bankId')->name('questions.store');

    // API-CSL-QST-002 题目详情
    Route::get('questions/{id}', [QuestionController::class, 'show'])
        ->whereNumber('id')->name('questions.show');

    // API-CSL-QST-004 更新题目
    Route::put('questions/{id}', [QuestionController::class, 'update'])
        ->whereNumber('id')->name('questions.update');

    // API-CSL-QST-005 删除题目
    Route::delete('questions/{id}', [QuestionController::class, 'destroy'])
        ->whereNumber('id')->name('questions.destroy');

    // API-CSL-QST-006 批量删除题目
    Route::post('questions/batch-delete', [QuestionController::class, 'batchDelete'])
        ->name('questions.batch-delete');

    // API-CSL-QST-007 批量移动章节
    Route::post('questions/batch-move', [QuestionController::class, 'batchMove'])
        ->name('questions.batch-move');

    // -------------------------------------------------------------------------
    // 题库导入 · API-CSL-IMP-*
    // -------------------------------------------------------------------------
    // API-CSL-IMP-001 创建导入任务（同步占位）
    Route::post('import-tasks', [ImportTaskController::class, 'store'])
        ->name('import-tasks.store');

    // API-CSL-IMP-002 导入任务列表
    Route::get('import-tasks', [ImportTaskController::class, 'index'])
        ->name('import-tasks.index');

    // API-CSL-IMP-003 导入任务详情
    Route::get('import-tasks/{id}', [ImportTaskController::class, 'show'])
        ->whereNumber('id')->name('import-tasks.show');

    // API-CSL-IMP-004 删除导入任务
    Route::delete('import-tasks/{id}', [ImportTaskController::class, 'destroy'])
        ->whereNumber('id')->name('import-tasks.destroy');

    // API-CSL-IMP-005 导入模板说明
    Route::get('import-tasks/template', [ImportTaskController::class, 'template'])
        ->name('import-tasks.template');

    // -------------------------------------------------------------------------
    // 学习资料 · API-CSL-FIL-*
    // -------------------------------------------------------------------------
    // API-CSL-FIL-001 资料分类列表
    Route::get('file-categories', [FileCategoryController::class, 'index'])
        ->name('file-categories.index');

    // API-CSL-FIL-002 新建资料分类
    Route::post('file-categories', [FileCategoryController::class, 'store'])
        ->name('file-categories.store');

    // API-CSL-FIL-003 更新资料分类
    Route::put('file-categories/{id}', [FileCategoryController::class, 'update'])
        ->whereNumber('id')->name('file-categories.update');

    // API-CSL-FIL-004 删除资料分类
    Route::delete('file-categories/{id}', [FileCategoryController::class, 'destroy'])
        ->whereNumber('id')->name('file-categories.destroy');

    // API-CSL-FIL-005 资料列表
    Route::get('file-assets', [FileAssetController::class, 'index'])
        ->name('file-assets.index');

    // API-CSL-FIL-006 上传后登记
    Route::post('file-assets', [FileAssetController::class, 'store'])
        ->name('file-assets.store');

    // API-CSL-FIL-007 删除资料
    Route::delete('file-assets/{id}', [FileAssetController::class, 'destroy'])
        ->whereNumber('id')->name('file-assets.destroy');

    // API-CSL-FIL-008 获取下载地址
    Route::get('file-assets/{id}/url', [FileAssetController::class, 'url'])
        ->whereNumber('id')->name('file-assets.url');

    // API-CSL-FIL-009 七牛直传凭证
    Route::post('files/upload-token', [FileAssetController::class, 'uploadToken'])
        ->name('files.upload-token');

    // -------------------------------------------------------------------------
    // 我的错题 · API-CSL-WRG-*
    // -------------------------------------------------------------------------
    // API-CSL-WRG-001 错题列表
    Route::get('wrong-questions', [WrongQuestionController::class, 'index'])
        ->name('wrong-questions.index');

    // API-CSL-WRG-002 移除单条
    Route::delete('wrong-questions/{id}', [WrongQuestionController::class, 'destroy'])
        ->whereNumber('id')->name('wrong-questions.destroy');

    // API-CSL-WRG-003 批量移除
    Route::post('wrong-questions/batch-remove', [WrongQuestionController::class, 'batchRemove'])
        ->name('wrong-questions.batch-remove');

    // -------------------------------------------------------------------------
    // 考试记录 · API-CSL-EXM-*
    // -------------------------------------------------------------------------
    // API-CSL-EXM-001 考试记录列表
    Route::get('exam-records', [ExamRecordController::class, 'index'])
        ->name('exam-records.index');

    // API-CSL-EXM-002 考试记录详情
    Route::get('exam-records/{id}', [ExamRecordController::class, 'show'])
        ->whereNumber('id')->name('exam-records.show');

    // -------------------------------------------------------------------------
    // 订单与会员 · API-CSL-ORD-*
    // -------------------------------------------------------------------------
    // API-CSL-ORD-001 我的订单
    Route::get('orders', [OrderController::class, 'index'])
        ->name('orders.index');

    // API-CSL-ORD-002 订单详情
    Route::get('orders/{id}', [OrderController::class, 'show'])
        ->whereNumber('id')->name('orders.show');

    // API-CSL-ORD-003 我的会员
    Route::get('member', [OrderController::class, 'member'])
        ->name('member');

    // API-CSL-ORD-004 会员套餐
    Route::get('member-plans', [OrderController::class, 'plans'])
        ->name('member-plans');

    // -------------------------------------------------------------------------
    // 账号设置 · API-CSL-ACC-*
    // -------------------------------------------------------------------------
    // API-CSL-ACC-001 个人资料
    Route::get('account/profile', [AccountController::class, 'profile'])
        ->name('account.profile');

    // API-CSL-ACC-002 更新资料
    Route::put('account/profile', [AccountController::class, 'updateProfile'])
        ->name('account.profile.update');

    // API-CSL-ACC-003 修改密码
    Route::put('account/password', [AccountController::class, 'changePassword'])
        ->name('account.password');

    // API-CSL-ACC-004 换绑手机
    Route::put('account/mobile', [AccountController::class, 'changeMobile'])
        ->name('account.mobile');
});
