<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 用户域（user_*）
 * 登记文档：docs/03-数据库设计规范.md §2.3
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // user_accounts · 用户账号表
        // ------------------------------------------------------------------
        Schema::create('user_accounts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->string('uid', 16)->comment('用户展示 UID，注册时生成，全局唯一');
            $table->string('mobile', 20)->default('')->comment('手机号（不含国家码）；注销时改写为 deleted_{id}_{mobile} 以释放占用');
            $table->string('mobile_country_code', 8)->default('86')->comment('手机号国家码');
            $table->string('password', 100)->default('')->comment('登录密码，bcrypt 哈希；纯微信登录用户为空');
            $table->string('wx_mp_openid', 64)->default('')->comment('微信小程序 openid');
            $table->string('wx_unionid', 64)->default('')->comment('微信开放平台 unionid');
            $table->unsignedTinyInteger('register_source')->default(1)->comment('注册来源 1=手机号 2=微信小程序 3=后台导入');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1=正常 2=禁用 3=注销中 4=已注销');
            $table->dateTime('last_login_at')->nullable()->comment('最后登录时间');
            $table->string('last_login_ip', 45)->default('')->comment('最后登录 IP');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->unique('uid', 'uk_user_accounts_uid');
            $table->unique('mobile', 'uk_user_accounts_mobile');
            $table->index('wx_mp_openid', 'idx_user_accounts_wx_mp_openid');
            $table->index('status', 'idx_user_accounts_status');
            $table->comment('用户账号表');
        });

        // ------------------------------------------------------------------
        // user_profiles · 用户资料表（与账号 1:1）
        // ------------------------------------------------------------------
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('user_id')->comment('用户ID，关联 user_accounts.id');
            $table->string('nickname', 32)->default('')->comment('昵称');
            $table->string('avatar', 255)->default('')->comment('头像 object_key 或完整 URL');
            $table->unsignedTinyInteger('gender')->default(0)->comment('性别 0=未知 1=男 2=女');
            $table->date('birthday')->nullable()->comment('生日');
            $table->string('province', 32)->default('')->comment('省');
            $table->string('city', 32)->default('')->comment('市');
            $table->string('exam_target', 64)->default('')->comment('备考目标，如「一级建造师」');
            $table->string('bio', 255)->default('')->comment('个性签名');
            $table->unsignedInteger('study_days')->default(0)->comment('累计学习天数');
            $table->unsignedInteger('study_seconds')->default(0)->comment('累计学习时长（秒）');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->unique('user_id', 'uk_user_profiles_user_id');
            $table->comment('用户资料表');
        });

        // ------------------------------------------------------------------
        // user_members · 用户会员表（与账号 1:1）
        // ------------------------------------------------------------------
        Schema::create('user_members', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('user_id')->comment('用户ID，关联 user_accounts.id');
            $table->unsignedTinyInteger('level')->default(0)->comment('会员等级 0=普通 1=月卡 2=季卡 3=年卡 4=永久');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1=生效 2=已过期 3=已冻结');
            $table->dateTime('started_at')->nullable()->comment('生效时间');
            $table->dateTime('expired_at')->nullable()->comment('到期时间，永久会员为 NULL');
            $table->unsignedTinyInteger('source_type')->default(1)->comment('来源 1=购买 2=系统赠送 3=活动奖励');
            $table->unsignedBigInteger('last_order_id')->default(0)->comment('最近关联订单ID');
            $table->unsignedInteger('ai_import_quota')->default(0)->comment('剩余 AI 导题配额（份）');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->unique('user_id', 'uk_user_members_user_id');
            $table->index('expired_at', 'idx_user_members_expired_at');
            $table->index('status', 'idx_user_members_status');
            $table->comment('用户会员表');
        });

        // ------------------------------------------------------------------
        // user_daily_stats · 用户每日学习统计表（学习空间数据来源）
        // ------------------------------------------------------------------
        Schema::create('user_daily_stats', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->date('stat_date')->comment('统计日期');
            $table->unsignedInteger('answer_count')->default(0)->comment('当日作答题数');
            $table->unsignedInteger('right_count')->default(0)->comment('当日答对题数');
            $table->unsignedInteger('wrong_count')->default(0)->comment('当日答错题数');
            $table->unsignedInteger('practice_count')->default(0)->comment('当日练习次数');
            $table->unsignedInteger('exam_count')->default(0)->comment('当日考试次数');
            $table->unsignedInteger('duration_seconds')->default(0)->comment('当日学习时长（秒）');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->unique(['user_id', 'stat_date'], 'uk_user_daily_stats_user_id_stat_date');
            $table->comment('用户每日学习统计表');
        });

        // ------------------------------------------------------------------
        // user_wrong_questions · 用户错题表
        // ------------------------------------------------------------------
        Schema::create('user_wrong_questions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->unsignedBigInteger('question_id')->comment('题目ID');
            $table->unsignedBigInteger('bank_id')->default(0)->comment('所属题库ID，冗余便于按题库筛选');
            $table->unsignedInteger('wrong_count')->default(1)->comment('累计答错次数');
            $table->dateTime('last_wrong_at')->nullable()->comment('最近答错时间');
            $table->unsignedTinyInteger('source_type')->default(1)->comment('来源 1=练习 2=考试 3=错题重做');
            $table->string('last_answer', 500)->default('')->comment('最近一次错误作答内容');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1=在错题本 2=已移除 3=已掌握');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->unique(['user_id', 'question_id'], 'uk_user_wrong_questions_user_id_question_id');
            $table->index(['user_id', 'bank_id', 'status'], 'idx_user_wrong_questions_user_id_bank_id_status');
            $table->index(['user_id', 'last_wrong_at'], 'idx_user_wrong_questions_user_id_last_wrong_at');
            $table->comment('用户错题表');
        });

        // ------------------------------------------------------------------
        // user_favorite_questions · 用户收藏题表
        // ------------------------------------------------------------------
        Schema::create('user_favorite_questions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->unsignedBigInteger('question_id')->comment('题目ID');
            $table->unsignedBigInteger('bank_id')->default(0)->comment('所属题库ID');
            $table->string('folder_name', 32)->default('默认收藏夹')->comment('收藏夹名称');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->unique(['user_id', 'question_id'], 'uk_user_favorite_questions_user_id_question_id');
            $table->index(['user_id', 'created_at'], 'idx_user_favorite_questions_user_id_created_at');
            $table->comment('用户收藏题表');
        });

        // ------------------------------------------------------------------
        // user_practice_records · 用户练习记录表
        // ------------------------------------------------------------------
        Schema::create('user_practice_records', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->unsignedBigInteger('bank_id')->default(0)->comment('题库ID');
            $table->unsignedBigInteger('chapter_id')->default(0)->comment('章节ID，0=全部');
            $table->unsignedTinyInteger('practice_mode')->default(1)->comment('模式 1=顺序练习 2=随机练习 3=专项练习 4=错题重做 5=闪卡 6=斩题');
            $table->unsignedInteger('total_count')->default(0)->comment('本次练习题数');
            $table->unsignedInteger('answered_count')->default(0)->comment('已作答题数');
            $table->unsignedInteger('right_count')->default(0)->comment('答对题数');
            $table->unsignedInteger('wrong_count')->default(0)->comment('答错题数');
            $table->decimal('correct_rate', 5, 2)->default(0)->comment('正确率（百分比，如 87.50）');
            $table->unsignedInteger('duration_seconds')->default(0)->comment('练习时长（秒）');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1=进行中 2=已完成 3=已放弃');
            $table->dateTime('started_at')->nullable()->comment('开始时间');
            $table->dateTime('finished_at')->nullable()->comment('完成时间');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->index(['user_id', 'created_at'], 'idx_user_practice_records_user_id_created_at');
            $table->index(['user_id', 'bank_id', 'status'], 'idx_user_practice_records_user_id_bank_id_status');
            $table->comment('用户练习记录表');
        });

        // ------------------------------------------------------------------
        // user_question_notes · 用户题目笔记表（用户私有数据，业务层必须带 user_id 过滤）
        // ------------------------------------------------------------------
        Schema::create('user_question_notes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->unsignedBigInteger('question_id')->comment('题目ID');
            $table->unsignedBigInteger('bank_id')->default(0)->comment('所属题库ID');
            $table->text('content')->comment('笔记内容');
            $table->unsignedTinyInteger('is_public')->default(0)->comment('是否公开 0=私有 1=公开');
            $table->unsignedInteger('like_count')->default(0)->comment('点赞数');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->unique(['user_id', 'question_id'], 'uk_user_question_notes_user_id_question_id');
            $table->index(['user_id', 'created_at'], 'idx_user_question_notes_user_id_created_at');
            $table->comment('用户题目笔记表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_question_notes');
        Schema::dropIfExists('user_practice_records');
        Schema::dropIfExists('user_favorite_questions');
        Schema::dropIfExists('user_wrong_questions');
        Schema::dropIfExists('user_daily_stats');
        Schema::dropIfExists('user_members');
        Schema::dropIfExists('user_profiles');
        Schema::dropIfExists('user_accounts');
    }
};
