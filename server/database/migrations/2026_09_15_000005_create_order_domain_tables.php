<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 交易域（order_*）
 * 登记文档：docs/03-数据库设计规范.md §2.7
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // order_member_plans · 会员套餐表
        // ------------------------------------------------------------------
        Schema::create('order_member_plans', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->string('name', 64)->comment('套餐名称，如「连续包月」「年度会员」');
            $table->unsignedTinyInteger('level')->default(1)->comment('对应会员等级 1=月卡 2=季卡 3=年卡 4=永久');
            $table->unsignedInteger('duration_days')->default(30)->comment('有效天数，永久为 0');
            $table->decimal('price_amount', 10, 2)->default(0)->comment('现价（元）');
            $table->decimal('origin_amount', 10, 2)->default(0)->comment('原价（元），用于划线价');
            $table->string('description', 255)->default('')->comment('套餐说明');
            $table->string('benefits_json', 1000)->default('')->comment('权益列表 JSON');
            $table->unsignedInteger('ai_import_quota')->default(0)->comment('赠送 AI 导题配额（份）');
            $table->unsignedTinyInteger('is_recommend')->default(0)->comment('是否推荐 0=否 1=是');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序值');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1=上架 2=下架');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->index(['status', 'sort_order'], 'idx_order_member_plans_status_sort_order');
            $table->comment('会员套餐表');
        });

        // ------------------------------------------------------------------
        // order_orders · 订单表
        // ------------------------------------------------------------------
        Schema::create('order_orders', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->string('order_no', 32)->comment('订单号，规则 OD+yyyyMMddHHmmss+6位随机');
            $table->unsignedBigInteger('user_id')->comment('下单用户ID');
            $table->unsignedTinyInteger('order_type')->default(1)->comment('类型 1=会员 2=题库购买 3=学习资料购买');
            $table->unsignedBigInteger('biz_id')->default(0)->comment('商品ID（套餐ID / 题库ID / 资料ID）');
            $table->string('biz_title', 120)->default('')->comment('商品名称快照');
            $table->decimal('origin_amount', 10, 2)->default(0)->comment('订单原价（元）');
            $table->decimal('discount_amount', 10, 2)->default(0)->comment('优惠金额（元）');
            $table->decimal('pay_amount', 10, 2)->default(0)->comment('应付金额（元）');
            $table->unsignedTinyInteger('pay_channel')->default(1)->comment('支付渠道 1=微信支付 2=支付宝');
            $table->unsignedTinyInteger('status')->default(0)->comment('状态 0=待支付 1=已支付 2=已取消 3=已退款 4=已关闭');
            $table->string('client_platform', 20)->default('')->comment('下单端 mp-weixin / app-android / h5 / console');
            $table->string('remark', 255)->default('')->comment('备注');
            $table->dateTime('expired_at')->nullable()->comment('支付超时时间，超时自动关闭');
            $table->dateTime('paid_at')->nullable()->comment('支付时间');
            $table->dateTime('cancelled_at')->nullable()->comment('取消时间');
            $table->dateTime('refunded_at')->nullable()->comment('退款时间');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->unique('order_no', 'uk_order_orders_order_no');
            $table->index(['user_id', 'status'], 'idx_order_orders_user_id_status');
            $table->index(['user_id', 'created_at'], 'idx_order_orders_user_id_created_at');
            $table->index(['status', 'created_at'], 'idx_order_orders_status_created_at');
            $table->comment('订单表');
        });

        // ------------------------------------------------------------------
        // order_payments · 支付流水表（流水表，不做软删除）
        // ------------------------------------------------------------------
        Schema::create('order_payments', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('order_id')->comment('订单ID');
            $table->string('order_no', 32)->comment('订单号（冗余，便于对账）');
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->string('transaction_no', 64)->default('')->comment('第三方支付流水号');
            $table->unsignedTinyInteger('pay_channel')->default(1)->comment('渠道 1=微信支付 2=支付宝');
            $table->decimal('pay_amount', 10, 2)->default(0)->comment('实付金额（元）');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1=待支付 2=成功 3=失败 4=已退款');
            $table->unsignedTinyInteger('notify_count')->default(0)->comment('回调通知次数');
            $table->longText('notify_json')->nullable()->comment('回调原文（对账留档）');
            $table->string('fail_reason', 255)->default('')->comment('失败原因');
            $table->dateTime('paid_at')->nullable()->comment('支付成功时间');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');

            $table->index('order_id', 'idx_order_payments_order_id');
            $table->index('order_no', 'idx_order_payments_order_no');
            $table->index('transaction_no', 'idx_order_payments_transaction_no');
            $table->index(['status', 'created_at'], 'idx_order_payments_status_created_at');
            $table->comment('支付流水表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
        Schema::dropIfExists('order_orders');
        Schema::dropIfExists('order_member_plans');
    }
};
