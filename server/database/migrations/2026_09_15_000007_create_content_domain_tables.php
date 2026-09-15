<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 内容 / 运营域（content_*）
 * 客户端需要读取，故不使用 sys_ 前缀（sys_ 表客户端账号无任何权限）
 * 登记文档：docs/03-数据库设计规范.md §2.9
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // content_banners · 运营位表（首页轮播 / 推荐位 / 弹窗）
        // ------------------------------------------------------------------
        Schema::create('content_banners', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->string('position_code', 32)->comment('投放位置编码 home_top=首页轮播 home_recommend=首页推荐 mine_entry=我的页入口');
            $table->string('title', 64)->default('')->comment('标题');
            $table->string('subtitle', 120)->default('')->comment('副标题');
            $table->string('image', 255)->comment('图片地址（公开空间 URL 或 object_key）');
            $table->unsignedTinyInteger('link_type')->default(1)->comment('跳转类型 1=不跳转 2=题库 3=学习资料 4=外链 5=活动页');
            $table->string('link_value', 255)->default('')->comment('跳转目标值（题库ID / 资料ID / URL）');
            $table->string('client_scope', 32)->default('all')->comment('投放端 all / mp-weixin / app-android / h5');
            $table->unsignedInteger('click_count')->default(0)->comment('点击次数');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序值');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1=启用 2=停用');
            $table->dateTime('started_at')->nullable()->comment('投放开始时间');
            $table->dateTime('ended_at')->nullable()->comment('投放结束时间');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->index(['position_code', 'status', 'sort_order'], 'idx_content_banners_position_code_status_sort_order');
            $table->comment('运营位表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_banners');
    }
};
