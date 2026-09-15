<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 考试域（exam_*）
 * 登记文档：docs/03-数据库设计规范.md §2.6
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // exam_papers · 试卷表
        // ------------------------------------------------------------------
        Schema::create('exam_papers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->string('paper_no', 32)->comment('试卷编号，规则 EP+yyyyMMdd+6位序列');
            $table->unsignedBigInteger('user_id')->comment('所属用户ID');
            $table->unsignedBigInteger('bank_id')->default(0)->comment('来源题库ID，0=多题库组卷');
            $table->string('title', 120)->comment('试卷标题');
            $table->unsignedTinyInteger('paper_type')->default(1)->comment('类型 1=模拟考试 2=自测 3=错题卷 4=随机组卷');
            $table->unsignedTinyInteger('generate_mode')->default(1)->comment('组卷方式 1=随机抽题 2=按章节 3=按题型 4=手动选题');
            $table->unsignedInteger('question_count')->default(0)->comment('题目数');
            $table->decimal('total_score', 8, 2)->default(0)->comment('试卷总分');
            $table->decimal('pass_score', 8, 2)->default(0)->comment('及格分');
            $table->unsignedInteger('duration_minutes')->default(0)->comment('考试时长（分钟），0=不限时');
            $table->string('config_json', 1000)->default('')->comment('组卷规则 JSON（章节分布、题型分布等）');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1=正常 2=已作废');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->unique('paper_no', 'uk_exam_papers_paper_no');
            $table->index(['user_id', 'created_at'], 'idx_exam_papers_user_id_created_at');
            $table->index(['bank_id', 'paper_type'], 'idx_exam_papers_bank_id_paper_type');
            $table->comment('试卷表');
        });

        // ------------------------------------------------------------------
        // exam_paper_questions · 试卷题目关联表
        // ------------------------------------------------------------------
        Schema::create('exam_paper_questions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('paper_id')->comment('试卷ID');
            $table->unsignedBigInteger('question_id')->comment('题目ID');
            $table->unsignedBigInteger('bank_id')->default(0)->comment('题目所属题库ID');
            $table->unsignedInteger('sort_order')->default(0)->comment('试卷内题号顺序');
            $table->decimal('score', 5, 2)->default(0)->comment('本卷内该题分值');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->unique(['paper_id', 'question_id'], 'uk_exam_paper_questions_paper_id_question_id');
            $table->index(['paper_id', 'sort_order'], 'idx_exam_paper_questions_paper_id_sort_order');
            $table->index('question_id', 'idx_exam_paper_questions_question_id');
            $table->comment('试卷题目关联表');
        });

        // ------------------------------------------------------------------
        // exam_records · 考试记录表（流水表，不做软删除）
        // ------------------------------------------------------------------
        Schema::create('exam_records', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->string('record_no', 32)->comment('考试记录编号，规则 ER+yyyyMMdd+6位序列，交卷幂等键');
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->unsignedBigInteger('paper_id')->comment('试卷ID');
            $table->unsignedBigInteger('bank_id')->default(0)->comment('题库ID');
            $table->string('paper_title', 120)->default('')->comment('试卷标题（冗余，便于列表展示）');
            $table->unsignedInteger('total_count')->default(0)->comment('总题数');
            $table->unsignedInteger('answered_count')->default(0)->comment('已作答题数');
            $table->unsignedInteger('right_count')->default(0)->comment('答对题数');
            $table->unsignedInteger('wrong_count')->default(0)->comment('答错题数');
            $table->unsignedInteger('unanswer_count')->default(0)->comment('未作答题数');
            $table->decimal('total_score', 8, 2)->default(0)->comment('试卷总分');
            $table->decimal('get_score', 8, 2)->default(0)->comment('实得分');
            $table->decimal('correct_rate', 5, 2)->default(0)->comment('正确率（百分比）');
            $table->unsignedInteger('duration_seconds')->default(0)->comment('作答耗时（秒）');
            $table->unsignedTinyInteger('is_passed')->default(0)->comment('是否及格 0=否 1=是');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1=进行中 2=已交卷 3=超时自动交卷 4=已作废');
            $table->dateTime('started_at')->nullable()->comment('开始时间');
            $table->dateTime('submitted_at')->nullable()->comment('交卷时间');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');

            $table->unique('record_no', 'uk_exam_records_record_no');
            $table->index(['user_id', 'created_at'], 'idx_exam_records_user_id_created_at');
            $table->index(['user_id', 'bank_id'], 'idx_exam_records_user_id_bank_id');
            $table->index('paper_id', 'idx_exam_records_paper_id');
            $table->comment('考试记录表');
        });

        // ------------------------------------------------------------------
        // exam_answers · 考试作答明细表（流水表，不做软删除）
        // ------------------------------------------------------------------
        Schema::create('exam_answers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('record_id')->comment('考试记录ID');
            $table->unsignedBigInteger('paper_id')->default(0)->comment('试卷ID');
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->unsignedBigInteger('question_id')->comment('题目ID');
            $table->string('user_answer', 500)->default('')->comment('用户作答内容');
            $table->unsignedTinyInteger('is_correct')->default(0)->comment('是否正确 0=否 1=是');
            $table->decimal('score', 5, 2)->default(0)->comment('本题得分');
            $table->unsignedInteger('duration_seconds')->default(0)->comment('本题作答耗时（秒）');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');

            $table->unique(['record_id', 'question_id'], 'uk_exam_answers_record_id_question_id');
            $table->index(['user_id', 'question_id'], 'idx_exam_answers_user_id_question_id');
            $table->comment('考试作答明细表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_answers');
        Schema::dropIfExists('exam_records');
        Schema::dropIfExists('exam_paper_questions');
        Schema::dropIfExists('exam_papers');
    }
};
