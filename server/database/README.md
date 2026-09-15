# 数据库层说明（database/）

> 权威规范见 `docs/03-数据库设计规范.md`（含**数据字典登记表**）。
> 表结构变更一律走 migration，**禁止手工改库**；改完必须回填数据字典。

## 一、目录结构

```
server/database/
├── migrations/                                   # 表结构（Laravel migration，唯一权威）
│   ├── 2026_09_15_000001_create_user_domain_tables.php
│   ├── 2026_09_15_000002_create_bank_domain_tables.php
│   ├── 2026_09_15_000003_create_question_domain_tables.php
│   ├── 2026_09_15_000004_create_exam_domain_tables.php
│   ├── 2026_09_15_000005_create_order_domain_tables.php
│   ├── 2026_09_15_000006_create_file_domain_tables.php
│   ├── 2026_09_15_000007_create_content_domain_tables.php
│   └── 2026_09_15_000008_create_sys_domain_tables.php
├── seeders/                                      # 初始化数据（幂等，可重复执行）
│   ├── DatabaseSeeder.php
│   ├── SystemInitSeeder.php                      # 权限点 / 角色 / 管理员 / 配置中心
│   └── BusinessInitSeeder.php                    # 分类 / 资料分类 / 会员套餐 / 运营位
└── sql/                                          # DBA 手工执行脚本
    ├── 00_create_database.sql                    # 建库
    └── 01_create_users_and_privileges.sql        # 三套账号与权限隔离
```

> **migration 按业务域分文件**，而非一表一文件：本项目为「模块化单体」，同域表的变更通常同步发生，
> 按域分文件可让变更评审更聚焦；表级粒度由文件内的 `Schema::create` 保证，仍可单独回滚。
> 域内新增表时，在本域文件末尾追加一段 `Schema::create`，并在 `down()` 中按**逆序**补 `dropIfExists`。

## 二、首次部署执行顺序

```bash
# ① 建库（DBA 身份）
mysql -uroot -p < server/database/sql/00_create_database.sql

# ② 配置后端 .env（先复制样例）
cp server/.env.example server/.env

# ③ 建表（应用身份，使用 app_admin 账号；migration 需要 DDL 权限）
cd server && php artisan migrate

# ④ 灌初始化数据（幂等，可重复执行）
php artisan db:seed

# ⑤ 授权与隔离（DBA 身份，先替换脚本里的三个密码占位符）
mysql -uroot -p < server/database/sql/01_create_users_and_privileges.sql

# ⑥ 校验：确认 sys_ 表未授权给 app_client / app_console
#    执行 01 脚本末尾的校验一查询，结果集应为空
```

> ⚠️ **顺序不能颠倒**：`app_admin` 只用于部署与总后台；客户端与用户后台必须改用
> `app_client` / `app_console`，它们**没有 DDL 权限**，无法建表或改表——这是隔离设计的一部分。

## 三、常用命令

| 场景 | 命令 |
| --- | --- |
| 建表 | `php artisan migrate` |
| 回滚最近一批 | `php artisan migrate:rollback` |
| 重置并重建（**仅开发环境**） | `php artisan migrate:fresh --seed` |
| 查看表状态 | `php artisan migrate:status` |
| 只跑初始化数据 | `php artisan db:seed` |
| 只跑指定 seeder | `php artisan db:seed --class=SystemInitSeeder` |
| 新增一张表 | `php artisan make:migration create_xxx_table` |

## 四、新增表 / 改表的标准动作

1. 在 `docs/03` 表清单总览中登记一行（表名 / 中文名 / 模块 / 负责人 / 状态）
2. 在对应域的 migration 文件中新增 `Schema::create` 或变更段
3. 在 `docs/03` 补写该表的**字段级数据字典**（字段 / 类型 / 允许空 / 默认 / 说明 / 备注）
4. 同步 `docs/03` §四 变更登记表（日期 / 表名 / 变更类型 / 变更内容 / 执行人 / 关联需求）
5. 涉及枚举的，同步 `app/Enums/` 与 `docs/03` §三枚举登记
6. 执行 `php artisan migrate` 并在测试环境验证后，方可上生产

## 五、数据字典覆盖范围（33 张表）

| 域 | 表数 | 表清单 |
| --- | --- | --- |
| 用户域 `user_` | 8 | `user_accounts` `user_profiles` `user_members` `user_daily_stats` `user_wrong_questions` `user_favorite_questions` `user_practice_records` `user_question_notes` |
| 题库域 `bank_` | 3 | `bank_categories` `bank_question_banks` `bank_chapters` |
| 题目域 `question_` | 4 | `question_items` `question_options` `question_reports` `question_import_tasks` |
| 考试域 `exam_` | 4 | `exam_papers` `exam_paper_questions` `exam_records` `exam_answers` |
| 交易域 `order_` | 3 | `order_member_plans` `order_orders` `order_payments` |
| 文件域 `file_` | 2 | `file_categories` `file_assets` |
| 内容域 `content_` | 1 | `content_banners` |
| 系统域 `sys_` | 8 | `sys_admins` `sys_roles` `sys_permissions` `sys_role_permissions` `sys_admin_roles` `sys_configs` `sys_logs` `sys_login_logs` |
| **合计** | **33** | — |

## 六、字段约定速查

| 约定 | 说明 |
| --- | --- |
| 通用四字段 | `id` / `created_at` / `updated_at` / `deleted_at`（流水表无 `deleted_at`） |
| 流水表 | `exam_records` `exam_answers` `order_payments` `sys_logs` `sys_login_logs` 保留 `created_at`（+`updated_at`），不做软删 |
| 软删除 | 模型统一使用 `Illuminate\Database\Eloquent\SoftDeletes` |
| 时间写入 | 全部由应用层显式写入，不使用数据库 `DEFAULT CURRENT_TIMESTAMP`，避免时区歧义 |
| 时区 | 应用统一 `Asia/Shanghai`，`.env` 设 `APP_TIMEZONE=Asia/Shanghai`，MySQL 连接时区 `+08:00` |
| 金额 | 一律 `decimal(10,2)`，单位元；禁止 `float` |
| 计数冗余 | `question_count` `practice_count` 等为冗余字段，由 Service 层在同一事务内维护，且提供 `php artisan data:recount` 校准命令 |
