<?php

use App\Http\Controllers\Api\V1\Bank\BankCategoryController;
use App\Http\Controllers\Api\V1\Bank\QuestionBankController;
use App\Http\Controllers\Api\V1\Common\ConfigController;
use App\Http\Controllers\Api\V1\File\FileController;
use App\Http\Controllers\Api\V1\User\AuthController;
use Illuminate\Support\Facades\Route;

/**
 * 客户端接口路由（前缀 /api/v1，中间件组 client）
 * 台账：docs/04-API接口规范与登记表.md §三
 *
 * ⚠️ 约定：本文件中的每一条路由都必须先在 04 文档登记编号，再写代码。
 *          未登记的接口视为不存在。
 *
 * 鉴权说明：
 *   - 无需登录：不挂 auth:client
 *   - 需要登录：挂 auth:client + user.active
 */

// =============================================================================
// 认证模块 · API-AUTH-*
// =============================================================================
Route::prefix('auth')->name('auth.')->group(function () {
    // API-AUTH-001 发送短信验证码（60s/次，10 次/天/手机号）
    Route::post('sms-code', [AuthController::class, 'sendSmsCode'])
        ->middleware('throttle:sms-per-mobile')
        ->name('sms-code');

    // API-AUTH-002 手机号登录 / 注册
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:auth-login')
        ->name('login');

    // API-AUTH-003 微信小程序登录（code2Session）
    Route::post('wechat-login', [AuthController::class, 'wechatLogin'])
        ->middleware('throttle:auth-login')
        ->name('wechat-login');

    // API-AUTH-004 刷新 Token
    Route::post('refresh', [AuthController::class, 'refresh'])
        ->middleware('auth:client')
        ->name('refresh');

    // API-AUTH-005 退出登录
    Route::post('logout', [AuthController::class, 'logout'])
        ->middleware('auth:client')
        ->name('logout');
});

// =============================================================================
// 配置模块 · API-CFG-*
// =============================================================================
// API-CFG-001 客户端启动配置（站点信息、最低版本、功能开关，无需登录）
Route::get('config/boot', [ConfigController::class, 'boot'])->name('config.boot');

// =============================================================================
// 题库模块 · API-BANK-*
// =============================================================================
Route::middleware(['auth:client', 'user.active'])->group(function () {
    // API-BANK-001 题库分类列表
    Route::get('bank-categories', [BankCategoryController::class, 'index'])->name('bank-categories.index');

    // API-BANK-002 我的题库列表
    Route::get('question-banks', [QuestionBankController::class, 'index'])->name('question-banks.index');

    // API-BANK-004 创建题库
    Route::post('question-banks', [QuestionBankController::class, 'store'])->name('question-banks.store');

    // API-BANK-007 题库市场列表（官方 / 推荐题库）
    Route::get('bank-market', [QuestionBankController::class, 'market'])->name('bank-market.index');

    // API-BANK-003 题库详情
    Route::get('question-banks/{id}', [QuestionBankController::class, 'show'])
        ->whereNumber('id')->name('question-banks.show');

    // API-BANK-005 更新 / 重命名题库
    Route::put('question-banks/{id}', [QuestionBankController::class, 'update'])
        ->whereNumber('id')->name('question-banks.update');

    // API-BANK-006 删除题库
    Route::delete('question-banks/{id}', [QuestionBankController::class, 'destroy'])
        ->whereNumber('id')->name('question-banks.destroy');
});

// =============================================================================
// 文件模块 · API-FIL-*（七牛云直传，详见 docs/05）
// =============================================================================
Route::middleware(['auth:client', 'user.active'])->prefix('files')->name('files.')->group(function () {
    // API-FIL-001 获取 OSS 直传凭证
    Route::post('upload-token', [FileController::class, 'uploadToken'])->name('upload-token');

    // API-FIL-002 上传完成回调登记
    Route::post('complete', [FileController::class, 'complete'])->name('complete');

    // API-FIL-003 学习资料列表
    Route::get('assets', [FileController::class, 'index'])->name('assets.index');

    // API-FIL-004 获取私有文件签名下载地址
    Route::get('assets/{id}/url', [FileController::class, 'signedUrl'])
        ->whereNumber('id')->name('assets.signed-url');
});

// =============================================================================
// 待开发接口（已在 04 文档登记编号，实现后取消注释并移入上方分组）
// -----------------------------------------------------------------------------
// 用户     API-USER-001 GET    /user/profile
//          API-USER-002 PUT    /user/profile
//          API-USER-003 GET    /user/study-summary
// 导入     API-IMP-001 POST   /import/upload
//          API-IMP-002 GET    /import/tasks/{id}
//          API-IMP-003 GET    /import/template
//          API-IMP-004 POST   /import/manual
//          API-IMP-005 POST   /import/ocr
// 题目     API-QUE-001 GET    /question-banks/{id}/questions
//          API-QUE-002 POST   /questions/{id}/answer
//          API-QUE-003 POST   /questions/{id}/favorite
//          API-QUE-004 PUT    /questions/{id}/note
//          API-QUE-005 POST   /questions/{id}/report
// 错题     API-WRG-001 GET    /wrong-questions
//          API-WRG-002 DELETE /wrong-questions/{id}
// 考试     API-EXM-001 POST  /exam-papers
//          API-EXM-002 GET    /exam-papers/{id}
//          API-EXM-003 POST   /exam-records
//          API-EXM-004 GET    /exam-records/{id}
//          API-EXM-005 GET    /exam-records
// 搜索     API-SRC-001 GET    /search/questions
//          API-SRC-002 POST   /search/solve
// 会员     API-MBR-001 GET    /member/plans
//          API-MBR-002 POST   /member/orders
// 订单     API-ORD-001 GET    /orders
// 支付     API-PAY-001 POST  /pay/wechat/prepay
//          API-PAY-002 POST   /pay/wechat/notify   ← 免登录，走验签，不要挂 auth:client
// =============================================================================
