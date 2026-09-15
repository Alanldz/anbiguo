<?php

use App\Http\Controllers\Api\V1\Bank\BankCategoryController;
use App\Http\Controllers\Api\V1\Bank\QuestionBankController;
use App\Http\Controllers\Api\V1\Bank\QuestionPracticeController;
use App\Http\Controllers\Api\V1\Common\ConfigController;
use App\Http\Controllers\Api\V1\Common\EventController;
use App\Http\Controllers\Api\V1\Exam\ExamController;
use App\Http\Controllers\Api\V1\File\FileController;
use App\Http\Controllers\Api\V1\Import\ImportController;
use App\Http\Controllers\Api\V1\Member\MemberController;
use App\Http\Controllers\Api\V1\Notification\NotificationController;
use App\Http\Controllers\Api\V1\Order\OrderController;
use App\Http\Controllers\Api\V1\Search\SearchController;
use App\Http\Controllers\Api\V1\User\AuthController;
use App\Http\Controllers\Api\V1\User\ProfileController;
use App\Http\Controllers\Api\V1\Wrong\WrongQuestionController;
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

    // API-BANK-008 回收站列表（务必放在 question-banks/{id} 之前，避免被 {id} 吞掉）
    Route::get('question-banks/recycle', [QuestionBankController::class, 'recycle'])
        ->name('question-banks.recycle');

    // API-BANK-003 题库详情
    Route::get('question-banks/{id}', [QuestionBankController::class, 'show'])
        ->whereNumber('id')->name('question-banks.show');

    // API-BANK-005 更新 / 重命名题库
    Route::put('question-banks/{id}', [QuestionBankController::class, 'update'])
        ->whereNumber('id')->name('question-banks.update');

    // API-BANK-006 删除题库
    Route::delete('question-banks/{id}', [QuestionBankController::class, 'destroy'])
        ->whereNumber('id')->name('question-banks.destroy');

    // API-BANK-009 恢复题库
    Route::put('question-banks/{id}/restore', [QuestionBankController::class, 'restore'])
        ->whereNumber('id')->name('question-banks.restore');
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
// 用户模块 · API-USER-*（需登录）
// =============================================================================
Route::middleware(['auth:client', 'user.active'])->group(function () {
    // API-USER-001 获取个人资料
    Route::get('user/profile', [ProfileController::class, 'show'])->name('user.profile.show');

    // API-USER-002 更新个人资料
    Route::put('user/profile', [ProfileController::class, 'update'])->name('user.profile.update');

    // API-USER-003 我的学习空间统计
    Route::get('user/study-summary', [ProfileController::class, 'studySummary'])->name('user.study-summary');

    // API-USER-004 账号注销
    Route::post('user/cancel', [ProfileController::class, 'cancel'])->name('user.cancel');
});

// =============================================================================
// 导入模块 · API-IMP-*（需登录，upload/ocr 走配额）
// =============================================================================
Route::middleware(['auth:client', 'user.active'])->prefix('import')->name('import.')->group(function () {
    // API-IMP-001 上传文档导题（同步占位）
    Route::post('upload', [ImportController::class, 'upload'])->name('upload');

    // API-IMP-004 手动录入题目（同步占位）
    Route::post('manual', [ImportController::class, 'manual'])->name('manual');

    // API-IMP-005 拍照录题 OCR（同步占位）
    Route::post('ocr', [ImportController::class, 'ocr'])->name('ocr');

    // API-IMP-002 查询解析进度
    Route::get('tasks/{id}', [ImportController::class, 'tasksShow'])
        ->whereNumber('id')->name('tasks.show');

    // API-IMP-003 下载导入模板
    Route::get('template', [ImportController::class, 'template'])->name('template');
});

// =============================================================================
// 题目与练习模块 · API-QUE-*（需登录）
// =============================================================================
Route::middleware(['auth:client', 'user.active'])->group(function () {
    // API-QUE-001 题目列表（练习取题）
    Route::get('question-banks/{id}/questions', [QuestionPracticeController::class, 'index'])
        ->whereNumber('id')->name('questions.index');

    // API-QUE-002 提交单题作答
    Route::post('questions/{id}/answer', [QuestionPracticeController::class, 'answer'])
        ->whereNumber('id')->name('questions.answer');

    // API-QUE-003 收藏 / 取消收藏
    Route::post('questions/{id}/favorite', [QuestionPracticeController::class, 'favorite'])
        ->whereNumber('id')->name('questions.favorite');

    // API-QUE-004 写 / 改笔记
    Route::put('questions/{id}/note', [QuestionPracticeController::class, 'note'])
        ->whereNumber('id')->name('questions.note');

    // API-QUE-005 试题报错
    Route::post('questions/{id}/report', [QuestionPracticeController::class, 'report'])
        ->whereNumber('id')->name('questions.report');

    // API-FAV-001 我的收藏列表
    Route::get('favorites', [QuestionPracticeController::class, 'favorites'])->name('favorites.index');

    // API-NOTE-001 我的笔记列表
    Route::get('notes', [QuestionPracticeController::class, 'notes'])->name('notes.index');

    // API-REC-001 练习记录列表
    Route::get('practice-records', [QuestionPracticeController::class, 'practiceRecords'])
        ->name('practice-records.index');
});

// =============================================================================
// 错题模块 · API-WRG-*（需登录）
// =============================================================================
Route::middleware(['auth:client', 'user.active'])->group(function () {
    // API-WRG-001 错题列表
    Route::get('wrong-questions', [WrongQuestionController::class, 'index'])->name('wrong-questions.index');

    // API-WRG-002 移除错题
    Route::delete('wrong-questions/{id}', [WrongQuestionController::class, 'remove'])
        ->whereNumber('id')->name('wrong-questions.remove');

    // API-MST-001 我的斩题列表（已掌握题目）
    Route::get('mastered-questions', [WrongQuestionController::class, 'mastered'])
        ->name('mastered-questions.index');

    // API-MST-002 找回已掌握题目
    Route::put('mastered-questions/{id}/restore', [WrongQuestionController::class, 'restoreMastered'])
        ->whereNumber('id')->name('mastered-questions.restore');

    // API-ERR-001 易错题集（按题库维度，correct_rate 升序）
    Route::get('error-prone-questions', [WrongQuestionController::class, 'errorProne'])
        ->name('error-prone-questions.index');
});

// =============================================================================
// 消息通知模块 · API-MSG-*（需登录）
// =============================================================================
Route::middleware(['auth:client', 'user.active'])->prefix('notifications')->name('notifications.')->group(function () {
    // API-MSG-002 未读通知数量（放在无参路由组，避免与带参路由混淆）
    Route::get('unread-count', [NotificationController::class, 'unreadCount'])
        ->name('unread-count');

    // API-MSG-004 全部已读
    Route::put('read-all', [NotificationController::class, 'readAll'])
        ->name('read-all');

    // API-MSG-001 通知列表
    Route::get('/', [NotificationController::class, 'index'])->name('index');

    // API-MSG-003 标记单条已读（幂等）
    Route::put('{id}/read', [NotificationController::class, 'markRead'])
        ->whereNumber('id')->name('read');
});

// =============================================================================
// 考试模块 · API-EXM-*（需登录，交卷幂等）
// =============================================================================
Route::middleware(['auth:client', 'user.active'])->name('exam.')->group(function () {
    // API-EXM-001 发起 / 生成试卷
    Route::post('exam-papers', [ExamController::class, 'storePaper'])->name('papers.store');

    // API-EXM-002 试卷详情（含题目）
    Route::get('exam-papers/{id}', [ExamController::class, 'showPaper'])
        ->whereNumber('id')->name('papers.show');

    // API-EXM-003 交卷（幂等）
    Route::post('exam-records', [ExamController::class, 'submit'])->name('records.store');

    // API-EXM-004 成绩与试卷回顾
    Route::get('exam-records/{id}', [ExamController::class, 'showRecord'])
        ->whereNumber('id')->name('records.show');

    // API-EXM-005 考试记录列表
    Route::get('exam-records', [ExamController::class, 'records'])->name('records.index');
});

// =============================================================================
// 会员模块 · API-MBR-*（需登录）
// =============================================================================
Route::middleware(['auth:client', 'user.active'])->prefix('member')->name('member.')->group(function () {
    // API-MBR-001 会员权益与套餐列表
    Route::get('plans', [MemberController::class, 'plans'])->name('plans');

    // API-MBR-002 开通会员下单（返回待支付订单，支付留待 API-PAY-001/002）
    Route::post('orders', [MemberController::class, 'storeOrder'])->name('orders.store');
});

// =============================================================================
// 订单模块 · API-ORD-*（需登录，仅本人订单）
// =============================================================================
Route::middleware(['auth:client', 'user.active'])->group(function () {
    // API-ORD-001 我的订单列表
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
});

// =============================================================================
// 搜索模块 · API-SRC-*（需登录，不依赖 AI）
// =============================================================================
Route::middleware(['auth:client', 'user.active'])->prefix('search')->name('search.')->group(function () {
    // API-SRC-001 题库内搜索试题
    Route::get('questions', [SearchController::class, 'questions'])->name('questions');
});

// =============================================================================
// 反馈模块 · API-FBK-*（需登录）
// =============================================================================
Route::middleware(['auth:client', 'user.active'])->group(function () {
    // API-FBK-001 提交意见反馈
    Route::post('feedbacks', [FeedbackController::class, 'store'])->name('feedbacks.store');
});

// =============================================================================
// 埋点模块 · API-EVT-*（需登录，append-only 落库 sys_event_logs）
// =============================================================================
Route::middleware(['auth:client', 'user.active'])->group(function () {
    // API-EVT-001 埋点批量上报（60/分钟/用户）
    Route::post('events/report', [EventController::class, 'report'])
        ->middleware('throttle:events-report')
        ->name('events.report');
});
