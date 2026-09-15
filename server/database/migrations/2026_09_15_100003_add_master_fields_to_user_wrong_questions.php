<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 用户错题表补充字段 —— 斩题机制（连续答对自动掌握）
 * 登记文档：docs/04-API接口规范与登记表.md §二 API-MST-001 ~ 002
 *
 * 状态语义沿用 user_wrong_questions.status 既有注释：1=在错题本 2=已移除 3=已掌握。
 * ⚠️ 仅新增本迁移，不改动任何现有迁移（docs/04 硬性红线）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_wrong_questions', function (Blueprint $table) {
            $table->unsignedInteger('right_streak')->default(0)
                ->after('wrong_count')->comment('连续答对次数（斩题计数）');
            $table->dateTime('mastered_at')->nullable()
                ->after('last_wrong_at')->comment('掌握（斩掉）时间');
        });
    }

    public function down(): void
    {
        Schema::table('user_wrong_questions', function (Blueprint $table) {
            $table->dropColumn(['right_streak', 'mastered_at']);
        });
    }
};
