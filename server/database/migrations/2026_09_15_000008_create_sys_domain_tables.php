<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 系统域（sys_*）—— 仅总后台专属
 * 客户端账号 app_client / 用户后台账号 app_console 对这组表无任何权限（见 docs/06 §二）
 * 登记文档：docs/03-数据库设计规范.md §2.10
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // sys_admins · 系统管理员表
        // ------------------------------------------------------------------
        Schema::create('sys_admins', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->string('username', 32)->comment('登录账号，全局唯一');
            $table->string('password', 100)->comment('登录密码，bcrypt 哈希');
            $table->string('real_name', 32)->default('')->comment('真实姓名');
            $table->string('mobile', 20)->default('')->comment('手机号');
            $table->string('email', 64)->default('')->comment('邮箱');
            $table->string('avatar', 255)->default('')->comment('头像');
            $table->unsignedTinyInteger('is_super')->default(0)->comment('是否超级管理员 0=否 1=是（拥有全部权限，不可删除）');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1=正常 2=禁用');
            $table->unsignedTinyInteger('login_fail_count')->default(0)->comment('连续登录失败次数，达 5 次锁定');
            $table->dateTime('locked_until')->nullable()->comment('锁定截止时间');
            $table->dateTime('last_login_at')->nullable()->comment('最后登录时间');
            $table->string('last_login_ip', 45)->default('')->comment('最后登录 IP');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->unique('username', 'uk_sys_admins_username');
            $table->index('status', 'idx_sys_admins_status');
            $table->comment('系统管理员表');
        });

        // ------------------------------------------------------------------
        // sys_roles · 角色表
        // ------------------------------------------------------------------
        Schema::create('sys_roles', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->string('name', 32)->comment('角色名称，如「运营」「客服」「财务」');
            $table->string('code', 32)->comment('角色编码，小写下划线，全局唯一');
            $table->string('description', 255)->default('')->comment('角色描述');
            $table->unsignedTinyInteger('is_system')->default(0)->comment('是否系统预置 0=否 1=是（不可删除）');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序值');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1=正常 2=停用');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->unique('code', 'uk_sys_roles_code');
            $table->comment('角色表');
        });

        // ------------------------------------------------------------------
        // sys_permissions · 权限点表
        // ------------------------------------------------------------------
        Schema::create('sys_permissions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父级权限ID，0=顶级菜单');
            $table->string('name', 64)->comment('权限名称，如「配置中心」');
            $table->string('code', 64)->comment('权限编码，格式 模块:资源:动作，如 sys:config:update');
            $table->unsignedTinyInteger('type')->default(1)->comment('类型 1=菜单 2=按钮 3=数据');
            $table->string('route_path', 128)->default('')->comment('前端路由路径');
            $table->string('icon', 64)->default('')->comment('菜单图标');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序值');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1=正常 2=停用');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->unique('code', 'uk_sys_permissions_code');
            $table->index(['parent_id', 'sort_order'], 'idx_sys_permissions_parent_id_sort_order');
            $table->comment('权限点表');
        });

        // ------------------------------------------------------------------
        // sys_role_permissions · 角色-权限关联表
        // ------------------------------------------------------------------
        Schema::create('sys_role_permissions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('role_id')->comment('角色ID');
            $table->unsignedBigInteger('permission_id')->comment('权限ID');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->unique(['role_id', 'permission_id'], 'uk_sys_role_permissions_role_id_permission_id');
            $table->index('permission_id', 'idx_sys_role_permissions_permission_id');
            $table->comment('角色-权限关联表');
        });

        // ------------------------------------------------------------------
        // sys_admin_roles · 管理员-角色关联表
        // ------------------------------------------------------------------
        Schema::create('sys_admin_roles', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('admin_id')->comment('管理员ID');
            $table->unsignedBigInteger('role_id')->comment('角色ID');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->unique(['admin_id', 'role_id'], 'uk_sys_admin_roles_admin_id_role_id');
            $table->index('role_id', 'idx_sys_admin_roles_role_id');
            $table->comment('管理员-角色关联表');
        });

        // ------------------------------------------------------------------
        // sys_configs · 系统配置表（配置中心，KEY 可视化修改）
        // ------------------------------------------------------------------
        Schema::create('sys_configs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->string('group_code', 32)->comment('配置分组 sms / wechat / payment / storage / ai / ocr / site');
            $table->string('config_key', 64)->comment('配置键，点号命名，如 storage.access_key');
            $table->text('config_value')->nullable()->comment('配置值；is_secret=1 时为 AES-256 加密串');
            $table->string('default_value', 255)->default('')->comment('默认值（非密文），用于恢复');
            $table->unsignedTinyInteger('value_type')->default(1)->comment('值类型 1=字符串 2=数字 3=布尔 4=JSON 5=密文');
            $table->unsignedTinyInteger('is_secret')->default(0)->comment('是否敏感 0=否 1=是（界面脱敏显示）');
            $table->string('title', 64)->default('')->comment('配置项中文名');
            $table->string('remark', 255)->default('')->comment('填写说明 / 获取途径');
            $table->unsignedTinyInteger('is_system')->default(0)->comment('是否系统内置 0=否 1=是（不可删除）');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序值');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1=启用 2=停用');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间（软删除）');

            $table->unique(['group_code', 'config_key'], 'uk_sys_configs_group_code_config_key');
            $table->index(['group_code', 'sort_order'], 'idx_sys_configs_group_code_sort_order');
            $table->comment('系统配置表（配置中心）');
        });

        // ------------------------------------------------------------------
        // sys_logs · 操作日志表（流水表，不做软删除）
        // ------------------------------------------------------------------
        Schema::create('sys_logs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('admin_id')->default(0)->comment('操作人ID（sys_admins.id），0=系统任务');
            $table->string('admin_name', 32)->default('')->comment('操作人账号（冗余留档）');
            $table->string('module', 32)->default('')->comment('模块，如 config / bank / user');
            $table->string('action', 32)->default('')->comment('动作，如 create / update / delete / audit');
            $table->string('description', 255)->default('')->comment('操作描述');
            $table->string('target_type', 64)->default('')->comment('目标对象类型，如 bank_question_bank');
            $table->unsignedBigInteger('target_id')->default(0)->comment('目标对象ID');
            $table->longText('before_json')->nullable()->comment('变更前数据快照');
            $table->longText('after_json')->nullable()->comment('变更后数据快照');
            $table->string('ip', 45)->default('')->comment('操作 IP');
            $table->string('user_agent', 255)->default('')->comment('User-Agent');
            $table->string('request_id', 64)->default('')->comment('链路追踪ID');
            $table->unsignedTinyInteger('result')->default(1)->comment('结果 1=成功 2=失败');
            $table->dateTime('created_at')->nullable()->comment('创建时间');

            $table->index(['admin_id', 'created_at'], 'idx_sys_logs_admin_id_created_at');
            $table->index(['module', 'action', 'created_at'], 'idx_sys_logs_module_action_created_at');
            $table->index(['target_type', 'target_id'], 'idx_sys_logs_target_type_target_id');
            $table->comment('操作日志表');
        });

        // ------------------------------------------------------------------
        // sys_login_logs · 管理员登录日志表（流水表，不做软删除）
        // ------------------------------------------------------------------
        Schema::create('sys_login_logs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id')->comment('主键');
            $table->unsignedBigInteger('admin_id')->default(0)->comment('管理员ID，登录失败且账号不存在时为 0');
            $table->string('username', 32)->default('')->comment('登录账号');
            $table->unsignedTinyInteger('login_type')->default(1)->comment('方式 1=账号密码 2=TOTP 二次验证');
            $table->string('ip', 45)->default('')->comment('登录 IP');
            $table->string('user_agent', 255)->default('')->comment('User-Agent');
            $table->unsignedTinyInteger('status')->default(1)->comment('结果 1=成功 2=失败');
            $table->string('message', 255)->default('')->comment('失败原因 / 备注');
            $table->dateTime('created_at')->nullable()->comment('创建时间');

            $table->index(['admin_id', 'created_at'], 'idx_sys_login_logs_admin_id_created_at');
            $table->index(['username', 'created_at'], 'idx_sys_login_logs_username_created_at');
            $table->index(['ip', 'created_at'], 'idx_sys_login_logs_ip_created_at');
            $table->comment('管理员登录日志表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_login_logs');
        Schema::dropIfExists('sys_logs');
        Schema::dropIfExists('sys_configs');
        Schema::dropIfExists('sys_admin_roles');
        Schema::dropIfExists('sys_role_permissions');
        Schema::dropIfExists('sys_permissions');
        Schema::dropIfExists('sys_roles');
        Schema::dropIfExists('sys_admins');
    }
};
