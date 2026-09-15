<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 系统域补充（sys_feedbacks）—— 意见反馈表
 * 登记文档：docs/03-数据库设计规范.md §2.10、docs/04 §五 API-ADM-104
 *
 * ⚠️ 仅新增本迁移，不改动任何现有迁移（docs/04 硬性红线）。
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // sys_feedbacks · 意见反馈表
        // ------------------------------------------------------------------
        Schema::create('sys_feedbacks', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('user_id')->default(0)->comment('反馈用户ID，关联 user_accounts.id，0=匿名');
            $table->unsignedTinyInteger('type')->default(3)->comment('反馈类型 1=功能异常 2=体验建议 3=其他');
            $table->string('content', 500)->comment('反馈内容');
            $table->string('images_json', 1000)->default('')->comment('截图 file_assets id 数组 JSON');
            $table->string('contact', 64)->default('')->comment('联系方式');
            $table->unsignedTinyInteger('status')->default(0)->comment('处理状态 0=待处理 1=已处理 2=已忽略');
            $table->string('reply', 500)->default('')->comment('处理回复');
            $table->unsignedBigInteger('handler_id')->default(0)->comment('处理管理员ID，关联 sys_admins.id');
            $table->dateTime('handled_at')->nullable()->comment('处理时间');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->index('user_id', 'idx_sys_feedbacks_user_id');
            $table->index('status', 'idx_sys_feedbacks_status');
            $table->comment('意见反馈表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_feedbacks');
    }
};
