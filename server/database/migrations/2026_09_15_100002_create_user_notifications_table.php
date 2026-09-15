<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 用户域补充（user_notifications）—— 用户通知表（消息通知中心）
 * 登记文档：docs/04-API接口规范与登记表.md §二 API-MSG-001 ~ 004
 *
 * ⚠️ 仅新增本迁移，不改动任何现有迁移（docs/04 硬性红线）。
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // user_notifications · 用户通知表
        // ------------------------------------------------------------------
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('user_id')->comment('接收用户ID，关联 user_accounts.id');
            $table->unsignedTinyInteger('type')->default(1)->comment('1=系统通知 2=互动通知 3=业务通知');
            $table->string('title', 128)->comment('通知标题');
            $table->string('content', 500)->default('')->comment('通知内容');
            $table->string('biz_type', 32)->default('')->comment('业务标识，如 order/refund/audit，空串=无关联业务');
            $table->unsignedBigInteger('biz_id')->default(0)->comment('关联业务ID，0=无关联业务');
            $table->unsignedTinyInteger('is_read')->default(0)->comment('0=未读 1=已读');
            $table->dateTime('read_at')->nullable()->comment('已读时间');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->index(['user_id', 'is_read'], 'idx_user_notifications_user_id_is_read');
            $table->index(['user_id', 'created_at'], 'idx_user_notifications_user_id_created_at');
            $table->comment('用户通知表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
