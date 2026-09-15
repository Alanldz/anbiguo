<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 文件域（file_*）—— OSS / 七牛云 Kodo 资源登记
 * 登记文档：docs/03-数据库设计规范.md §2.8、docs/05-OSS存储与文件分类规范.md
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // file_categories · 文件分类表（学习资料分类树，用户可自建）
        // ------------------------------------------------------------------
        Schema::create('file_categories', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('user_id')->default(0)->comment('归属用户ID，0=系统预置分类');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父级分类ID，0=顶级');
            $table->string('name', 64)->comment('分类名称，如「历年真题」「讲义笔记」');
            $table->string('code', 32)->comment('分类编码，小写下划线，同一用户下唯一');
            $table->string('icon', 255)->default('')->comment('分类图标');
            $table->unsignedInteger('file_count')->default(0)->comment('分类下文件数（冗余计数）');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序值');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1=正常 2=隐藏');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->unique(['user_id', 'code'], 'uk_file_categories_user_id_code');
            $table->index(['user_id', 'parent_id', 'sort_order'], 'idx_file_categories_user_id_parent_id_sort_order');
            $table->comment('文件分类表');
        });

        // ------------------------------------------------------------------
        // file_assets · 文件资源表（OSS 对象登记，唯一入口）
        // ------------------------------------------------------------------
        Schema::create('file_assets', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('user_id')->default(0)->comment('归属用户ID，0=系统文件');
            $table->unsignedTinyInteger('biz_type')->default(1)->comment('业务类型 1=题库源文件 2=题目图片 3=学习资料 4=课程音视频 5=头像 6=公开静态 9=临时文件');
            $table->unsignedBigInteger('bank_id')->default(0)->comment('归属题库ID，0=不属于题库');
            $table->unsignedBigInteger('category_id')->default(0)->comment('归属文件分类ID，0=未分类');
            $table->unsignedBigInteger('question_id')->default(0)->comment('归属题目ID，0=不属于题目');
            $table->string('origin_name', 255)->default('')->comment('用户原始文件名（仅留档展示，不作为存储路径）');
            $table->string('object_key', 500)->comment('存储对象 Key（OSS 路径），全局唯一');
            $table->string('file_ext', 16)->default('')->comment('扩展名（小写，不含点）');
            $table->unsignedBigInteger('file_size')->default(0)->comment('文件大小（字节）');
            $table->string('file_hash', 64)->default('')->comment('文件 MD5，用于秒传与去重');
            $table->string('mime_type', 100)->default('')->comment('MIME 类型');
            $table->string('storage', 20)->default('qiniu')->comment('存储方 qiniu / aliyun / local');
            $table->unsignedTinyInteger('is_public')->default(0)->comment('是否公开读 0=私有（签名URL） 1=公开');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1=待上传 2=已上传 3=解析中 4=已归档 5=失败');
            $table->unsignedInteger('ref_count')->default(0)->comment('引用计数，为 0 且软删才可清理 OSS 对象');
            $table->dateTime('expired_at')->nullable()->comment('临时文件过期时间，到期由定时任务清理');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->unique('object_key', 'uk_file_assets_object_key');
            $table->index(['user_id', 'biz_type'], 'idx_file_assets_user_id_biz_type');
            $table->index(['bank_id', 'category_id'], 'idx_file_assets_bank_id_category_id');
            $table->index('file_hash', 'idx_file_assets_file_hash');
            $table->index(['status', 'expired_at'], 'idx_file_assets_status_expired_at');
            $table->comment('文件资源表（OSS 登记）');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_assets');
        Schema::dropIfExists('file_categories');
    }
};
