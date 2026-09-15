<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 客户端埋点日志表（sys_event_logs，append-only，不软删）
 * 登记文档：docs/04-API接口规范与登记表.md §三 API-EVT-001
 *
 * ⚠️ 仅新增本迁移，不改动任何现有迁移（docs/04 硬性红线）。
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // sys_event_logs · 客户端埋点日志表
        // ------------------------------------------------------------------
        Schema::create('sys_event_logs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('user_id')->default(0)->comment('用户ID，关联 user_accounts.id，0=未登录（本期客户端仅登录后上报）');
            $table->string('event', 64)->comment('事件名：app_boot/page_view/question_answer/exam_submit/import_create 等');
            $table->string('page', 128)->default('')->comment('页面路径');
            $table->string('biz_type', 32)->default('')->comment('业务标识，如 exam/import/order，空串=无关联业务');
            $table->unsignedBigInteger('biz_id')->default(0)->comment('关联业务ID，0=无关联业务');
            $table->string('extra_json', 1000)->default('')->comment('扩展信息 JSON（序列化后≤800字）');
            $table->string('client_platform', 16)->default('')->comment('客户端平台，取请求头 X-Client-Platform：mp-weixin/app-android/h5');
            $table->dateTime('occurred_at')->nullable()->comment('客户端事件发生时间，为空取服务器时间');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');

            $table->index(['user_id', 'created_at'], 'idx_sys_event_logs_user_id_created_at');
            $table->index(['event', 'created_at'], 'idx_sys_event_logs_event_created_at');
            $table->comment('客户端埋点日志表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_event_logs');
    }
};
