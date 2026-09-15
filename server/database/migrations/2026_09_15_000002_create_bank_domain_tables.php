<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 题库域（bank_*）
 * 登记文档：docs/03-数据库设计规范.md §2.4
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // bank_categories · 题库分类表（首页左侧分类 / 题库市场）
        // ------------------------------------------------------------------
        Schema::create('bank_categories', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父级分类ID，0=顶级');
            $table->string('name', 32)->comment('分类名称，如「建筑工程」「财会经济」');
            $table->string('code', 32)->comment('分类编码，小写下划线，全局唯一，如 construction');
            $table->string('icon', 255)->default('')->comment('分类图标');
            $table->unsignedTinyInteger('level')->default(1)->comment('层级 1=一级 2=二级');
            $table->unsignedInteger('question_bank_count')->default(0)->comment('该分类下题库数（冗余计数）');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序值，越小越靠前');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1=正常 2=隐藏');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->unique('code', 'uk_bank_categories_code');
            $table->index(['parent_id', 'sort_order'], 'idx_bank_categories_parent_id_sort_order');
            $table->comment('题库分类表');
        });

        // ------------------------------------------------------------------
        // bank_question_banks · 题库主表
        // ------------------------------------------------------------------
        Schema::create('bank_question_banks', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('user_id')->default(0)->comment('归属用户ID，0=官方题库');
            $table->unsignedBigInteger('category_id')->default(0)->comment('分类ID，关联 bank_categories.id');
            $table->string('title', 120)->comment('题库名称');
            $table->string('subtitle', 255)->default('')->comment('副标题 / 简介');
            $table->string('cover', 255)->default('')->comment('封面图');
            $table->unsignedTinyInteger('source_type')->default(1)->comment('来源 1=用户上传 2=官方 3=购买 4=AI生成');
            $table->unsignedTinyInteger('charge_type')->default(1)->comment('收费方式 1=免费 2=会员免费 3=单独购买');
            $table->decimal('price_amount', 10, 2)->default(0)->comment('单独购买价格（元）');
            $table->unsignedInteger('question_count')->default(0)->comment('题目数（冗余计数）');
            $table->unsignedInteger('chapter_count')->default(0)->comment('章节数（冗余计数）');
            $table->unsignedInteger('practice_count')->default(0)->comment('累计练习次数');
            $table->unsignedInteger('user_count')->default(0)->comment('累计学习人数');
            $table->string('tags_json', 500)->default('')->comment('标签数组 JSON');
            $table->unsignedTinyInteger('is_top')->default(0)->comment('是否置顶 0=否 1=是');
            $table->unsignedTinyInteger('is_recommend')->default(0)->comment('是否推荐到首页 0=否 1=是');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序值，越小越靠前');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1=正常 2=隐藏 3=待审核 4=已拒绝');
            $table->string('audit_remark', 255)->default('')->comment('审核意见');
            $table->unsignedBigInteger('audited_by')->default(0)->comment('审核人ID（sys_admins.id）');
            $table->dateTime('audited_at')->nullable()->comment('审核时间');
            $table->dateTime('last_practice_at')->nullable()->comment('最近练习时间');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->index(['user_id', 'status'], 'idx_bank_question_banks_user_id_status');
            $table->index(['category_id', 'status'], 'idx_bank_question_banks_category_id_status');
            $table->index(['source_type', 'status'], 'idx_bank_question_banks_source_type_status');
            $table->index(['is_recommend', 'sort_order'], 'idx_bank_question_banks_is_recommend_sort_order');
            $table->comment('题库主表');
        });

        // ------------------------------------------------------------------
        // bank_chapters · 题库章节表（专项练习、章节分析用）
        // ------------------------------------------------------------------
        Schema::create('bank_chapters', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('bank_id')->comment('所属题库ID');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父级章节ID，0=顶级');
            $table->string('name', 120)->comment('章节名称');
            $table->unsignedTinyInteger('level')->default(1)->comment('层级 1=章 2=节');
            $table->unsignedInteger('question_count')->default(0)->comment('章节题目数（冗余计数）');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序值');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->index(['bank_id', 'sort_order'], 'idx_bank_chapters_bank_id_sort_order');
            $table->index(['bank_id', 'parent_id'], 'idx_bank_chapters_bank_id_parent_id');
            $table->comment('题库章节表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_chapters');
        Schema::dropIfExists('bank_question_banks');
        Schema::dropIfExists('bank_categories');
    }
};
