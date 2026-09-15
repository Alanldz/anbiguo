<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 题目域（question_*）
 * 登记文档：docs/03-数据库设计规范.md §2.5
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // question_items · 题目表
        // ------------------------------------------------------------------
        Schema::create('question_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('bank_id')->comment('所属题库ID');
            $table->unsignedBigInteger('chapter_id')->default(0)->comment('所属章节ID，0=未分章');
            $table->unsignedTinyInteger('question_type')->default(1)->comment('题型 1=单选 2=多选 3=判断 4=填空 5=简答');
            $table->text('stem')->comment('题干（支持富文本标记）');
            $table->string('stem_preview', 255)->default('')->comment('题干纯文本摘要，用于列表与搜索');
            $table->text('analysis')->comment('答案解析');
            $table->string('answer', 500)->default('')->comment('正确答案。选择题存选项字母（多选如 ABD）；判断题存 对/错；填空简答存文本');
            $table->unsignedTinyInteger('difficulty')->default(1)->comment('难度 1=易 2=中 3=难');
            $table->decimal('score', 5, 2)->default(0)->comment('分值');
            $table->string('media_json', 500)->default('')->comment('题干媒体资源 JSON（图片 / 音频）');
            $table->unsignedTinyInteger('source_type')->default(1)->comment('来源 1=手动录入 2=文档导入 3=拍照OCR 4=AI生成');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1=正常 2=隐藏 3=待审核');
            $table->unsignedInteger('answer_count')->default(0)->comment('累计作答次数（冗余统计）');
            $table->unsignedInteger('right_count')->default(0)->comment('累计答对次数（冗余统计）');
            $table->decimal('correct_rate', 5, 2)->default(0)->comment('正确率（百分比，冗余统计）');
            $table->unsignedInteger('sort_order')->default(0)->comment('题库内排序值');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->index(['bank_id', 'sort_order'], 'idx_question_items_bank_id_sort_order');
            $table->index(['bank_id', 'chapter_id'], 'idx_question_items_bank_id_chapter_id');
            $table->index(['bank_id', 'question_type'], 'idx_question_items_bank_id_question_type');
            $table->index('stem_preview', 'idx_question_items_stem_preview');
            $table->comment('题目表');
        });

        // ------------------------------------------------------------------
        // question_options · 题项（选项）表
        // ------------------------------------------------------------------
        Schema::create('question_options', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('question_id')->comment('所属题目ID');
            $table->unsignedBigInteger('bank_id')->default(0)->comment('所属题库ID，冗余便于批量删除');
            $table->string('option_key', 2)->comment('选项序号，A~H');
            $table->string('content', 1000)->comment('选项内容');
            $table->unsignedTinyInteger('is_correct')->default(0)->comment('是否正确答案 0=否 1=是');
            $table->string('media_json', 500)->default('')->comment('选项媒体资源 JSON');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序值');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->index(['question_id', 'sort_order'], 'idx_question_options_question_id_sort_order');
            $table->index('bank_id', 'idx_question_options_bank_id');
            $table->comment('题项（选项）表');
        });

        // ------------------------------------------------------------------
        // question_reports · 试题报错表
        // ------------------------------------------------------------------
        Schema::create('question_reports', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('question_id')->comment('题目ID');
            $table->unsignedBigInteger('bank_id')->default(0)->comment('所属题库ID');
            $table->unsignedBigInteger('user_id')->default(0)->comment('反馈用户ID');
            $table->unsignedTinyInteger('report_type')->default(1)->comment('类型 1=答案错误 2=解析错误 3=题干错误 4=图片错误 5=题目重复 9=其他');
            $table->string('content', 500)->default('')->comment('补充说明');
            $table->string('images_json', 1000)->default('')->comment('截图数组 JSON');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1=待处理 2=已处理 3=已忽略');
            $table->unsignedBigInteger('handled_by')->default(0)->comment('处理人ID（sys_admins.id）');
            $table->string('handle_remark', 255)->default('')->comment('处理意见');
            $table->dateTime('handled_at')->nullable()->comment('处理时间');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->index(['status', 'created_at'], 'idx_question_reports_status_created_at');
            $table->index(['question_id', 'status'], 'idx_question_reports_question_id_status');
            $table->index('user_id', 'idx_question_reports_user_id');
            $table->comment('试题报错表');
        });

        // ------------------------------------------------------------------
        // question_import_tasks · 导题解析任务表
        // ------------------------------------------------------------------
        Schema::create('question_import_tasks', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->string('task_no', 32)->comment('任务编号，规则 IMP+yyyyMMdd+6位序列');
            $table->unsignedBigInteger('user_id')->comment('发起用户ID');
            $table->unsignedBigInteger('bank_id')->default(0)->comment('目标题库ID，0=解析完成后新建');
            $table->unsignedBigInteger('file_id')->default(0)->comment('源文件ID，关联 file_assets.id');
            $table->string('origin_name', 255)->default('')->comment('用户上传的原始文件名');
            $table->string('file_ext', 16)->default('')->comment('源文件扩展名');
            $table->unsignedTinyInteger('import_mode')->default(1)->comment('方式 1=文档导入 2=手动录入 3=拍照OCR 4=试题答案分离');
            $table->unsignedInteger('total_count')->default(0)->comment('解析出的题目总数');
            $table->unsignedInteger('success_count')->default(0)->comment('解析成功题数');
            $table->unsignedInteger('fail_count')->default(0)->comment('解析失败题数');
            $table->unsignedTinyInteger('progress')->default(0)->comment('进度 0~100');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1=待解析 2=解析中 3=待校对 4=已完成 5=失败');
            $table->string('error_message', 500)->default('')->comment('失败原因');
            $table->longText('result_json')->nullable()->comment('解析结果（待校对数据），完成后可清理');
            $table->dateTime('started_at')->nullable()->comment('开始解析时间');
            $table->dateTime('finished_at')->nullable()->comment('完成时间');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->unique('task_no', 'uk_question_import_tasks_task_no');
            $table->index(['user_id', 'status'], 'idx_question_import_tasks_user_id_status');
            $table->index(['bank_id', 'status'], 'idx_question_import_tasks_bank_id_status');
            $table->comment('导题解析任务表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_import_tasks');
        Schema::dropIfExists('question_reports');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('question_items');
    }
};
