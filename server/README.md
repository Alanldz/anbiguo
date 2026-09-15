# 识途刷题 · 后端服务（server/）

> PHP 8.3 + Laravel 11 + MySQL 8.0 + Redis 7，前后端严格分离。
> 本应用同时承载**客户端接口**（`/api/v1`）与**用户后台接口**（`/console-api/v1`）。
> **总后台是独立应用**（`admin/api/`），不在此目录内，两者目录、域名、部署、密钥完全隔离。

---

## 一、目录结构

```
server/
├── app/
│   ├── Auth/                          # JwtGuard（三端通用）、TokenBlacklist
│   ├── Console/                       # （保留）后台相关扩展
│   ├── Enums/                         # 29 个枚举类，杜绝魔法数字
│   ├── Exceptions/
│   │   └── BusinessException.php      # 业务异常统一出口
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Controller.php         # 基类（分页、当前用户）
│   │   │   ├── Api/V1/                # 客户端控制器
│   │   │   │   ├── Bank/              # 题库、分类
│   │   │   │   ├── Common/            # 启动配置
│   │   │   │   ├── File/              # 文件与直传凭证
│   │   │   │   └── User/              # 认证
│   │   │   └── Console/V1/            # 用户后台控制器
│   │   ├── Middleware/                # 链路ID、平台标识、账号状态、操作日志
│   │   ├── Requests/                  # 入参校验（按模块分目录）
│   │   └── Resources/                 # 出参结构
│   ├── Models/                        # 33 个模型，与数据表一一对应
│   ├── Providers/                     # App / Auth / Storage 三个提供者
│   ├── Services/                      # 业务逻辑层（控制器不写业务）
│   │   ├── Bank/  Config/  Console/  File/  Sms/  Storage/  User/  Wechat/
│   └── Support/                       # ApiResponse / ErrorCode / RequestContext / Jwt
├── bootstrap/app.php                  # 路由注册 + 中间件 + 全局异常映射
├── config/                            # app / auth / database / cache / queue / logging / cors / anbiguo
├── database/                          # migrations（33 表）/ seeders / sql（建库与授权）
├── routes/                            # client.php、console_api.php、web.php、console.php
├── public/index.php                   # 唯一入口
└── .env.example
```

---

## 二、分层约定（**必须遵守**）

```
请求 → 中间件（链路ID / 平台 / 鉴权 / 账号状态）
     → Controller  只做：取参 → 调用 Service → ApiResponse 返回
     → Service     承载全部业务规则、事务、权限校验
     → Model       只做数据映射与关系定义
```

红线（`docs/02` §五）：

1. 控制器里**禁止**写 SQL、禁止写业务判断
2. 服务返回**必须**经 `App\Support\ApiResponse`，禁止手写 `response()->json()`
3. 可预期的业务失败**必须**抛 `BusinessException(ErrorCode::XXX)`，禁止抛通用 `Exception`
4. 状态值**禁止**写魔法数字，一律用 `App\Enums\` 中的枚举
5. 第三方 KEY **禁止**写进代码或 `.env`，一律存 `sys_configs` 并由 `ConfigCenter` 读取

---

## 三、三端鉴权（`docs/06` §二）

| 端 | 守卫 | 密钥环境变量 | 有效期 | 刷新 |
| --- | --- | --- | --- | --- |
| 客户端 | `auth:client` | `JWT_SECRET_CLIENT` | 7 天 | 支持 |
| 用户后台 | `auth:console` | `JWT_SECRET_CONSOLE` | 2 小时 | 不支持 |
| 总后台（独立应用） | `auth:admin` | `JWT_SECRET_ADMIN` | 30 分钟 | 不支持 |

Token 载荷含 `scp` 作用域字段，**跨端使用会被直接拒绝**（10401）。

---

## 四、本地开发

```bash
# 1. 依赖（需已安装 PHP 8.3 + Composer 2）
composer install

# 2. 环境变量
cp .env.example .env
php artisan key:generate
# 生成三套互不相同的 JWT 密钥并填入 .env
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"

# 3. 建库 + 建表 + 初始化数据（详见 database/README.md）
mysql -uroot -p < database/sql/00_create_database.sql
php artisan migrate
php artisan db:seed

# 4. 授权限（DBA 身份，先替换脚本里的密码占位符）
mysql -uroot -p < database/sql/01_create_users_and_privileges.sql

# 5. 启动
php artisan serve            # http://127.0.0.1:8000
php artisan queue:work redis --queue=default,import,notify,export
```

**本机若尚无 PHP 环境**：需先安装 PHP 8.3（含 `pdo_mysql`、`openssl`、`fileinfo`、`mbstring`、`curl` 扩展）、Composer 2、MySQL 8.0、Redis 7。源码已全部就绪，装好环境后按上面五步即可跑起来。

---

## 五、已实现接口（首批垂直切片）

| 编号 | 方法 | 路径 | 说明 |
| --- | --- | --- | --- |
| API-AUTH-001 | POST | `/api/v1/auth/sms-code` | 发送短信验证码（按手机号限流） |
| API-AUTH-002 | POST | `/api/v1/auth/login` | 手机号登录 / 注册 |
| API-AUTH-003 | POST | `/api/v1/auth/wechat-login` | 微信小程序登录（code2Session） |
| API-AUTH-004 | POST | `/api/v1/auth/refresh` | 刷新 Token |
| API-AUTH-005 | POST | `/api/v1/auth/logout` | 退出登录（jti 黑名单） |
| API-CFG-001 | GET | `/api/v1/config/boot` | 客户端启动配置（站点 + 运营位） |
| API-BANK-001 | GET | `/api/v1/bank-categories` | 题库分类列表 |
| API-BANK-002 | GET | `/api/v1/question-banks` | 我的题库列表（分页/搜索） |
| API-BANK-003 | GET | `/api/v1/question-banks/{id}` | 题库详情 |
| API-BANK-004 | POST | `/api/v1/question-banks` | 创建题库 |
| API-BANK-005 | PUT | `/api/v1/question-banks/{id}` | 更新 / 重命名题库 |
| API-BANK-006 | DELETE | `/api/v1/question-banks/{id}` | 删除题库 |
| API-BANK-007 | GET | `/api/v1/bank-market` | 题库市场列表 |
| API-FIL-001 | POST | `/api/v1/files/upload-token` | 获取七牛直传凭证 |
| API-FIL-002 | POST | `/api/v1/files/complete` | 上传完成登记 |
| API-FIL-003 | GET | `/api/v1/files/assets` | 学习资料列表 |
| API-FIL-004 | GET | `/api/v1/files/assets/{id}/url` | 私有文件签名地址 |
| API-CSL-AUTH-001 | POST | `/console-api/v1/auth/login` | 用户后台登录 |
| API-CSL-AUTH-002 | POST | `/console-api/v1/auth/logout` | 用户后台退出 |
| API-CSL-AUTH-003 | GET | `/console-api/v1/auth/me` | 当前用户信息 |
| API-CSL-AUTH-004 | POST | `/console-api/v1/auth/sms-code` | 用户后台登录验证码 |
| API-CSL-STAT-001 | GET | `/console-api/v1/statistics/overview` | 学习概览（含近 30 天趋势） |
| API-CSL-BANK-001~007 | GET/POST/PUT/DELETE | `/console-api/v1/question-banks*` | 题库 CRUD / 导出 / 分类树 |
| API-CSL-CHP-001~004 | GET/POST/PUT/DELETE | `/console-api/v1/question-banks/{bankId}/chapters*` | 章节 CRUD |
| API-CSL-QST-001~007 | GET/POST/PUT/DELETE | `/console-api/v1/questions*` | 题目 CRUD / 批量删除 / 批量移动 |
| API-CSL-IMP-001~005 | GET/POST/DELETE | `/console-api/v1/import-tasks*` | 导入任务（AI 解析占位） |
| API-CSL-FIL-001~009 | GET/POST/PUT/DELETE | `/console-api/v1/file-categories*` `file-assets*` `files/upload-token` | 资料分类 / 文件 / 直传 |
| API-CSL-WRG-001~003 | GET/DELETE/POST | `/console-api/v1/wrong-questions*` | 错题管理 |
| API-CSL-EXM-001~002 | GET | `/console-api/v1/exam-records*` | 考试记录 |
| API-CSL-ORD-001~004 | GET | `/console-api/v1/orders*` `member*` | 订单与会员 |
| API-CSL-ACC-001~004 | GET/PUT | `/console-api/v1/account/*` | 资料 / 密码 / 换绑手机 |
| API-USER-001~002 | GET/PUT | `/api/v1/user/profile` | 个人资料读写 |
| API-USER-003 | GET | `/api/v1/user/study-summary` | 学习空间统计 |
| API-IMP-001~005 | GET/POST | `/api/v1/import/*` | 导题（AI 解析占位）/ 模板 / 手动录入 / OCR |
| API-QUE-001~005 | GET/POST/PUT | `/api/v1/question-banks/{id}/questions` `questions/{id}/*` | 练习取题 / 作答 / 收藏 / 笔记 / 报错 |
| API-WRG-001~002 | GET/DELETE | `/api/v1/wrong-questions*` | 错题列表 / 移除 |
| API-EXM-001~005 | GET/POST | `/api/v1/exam-papers*` `exam-records*` | 组卷 / 交卷（幂等）/ 成绩 / 记录 |
| API-MBR-001~002 | GET/POST | `/api/v1/member/*` | 套餐列表 / 开通下单（待支付，支付待接入） |
| API-ORD-001 | GET | `/api/v1/orders` | 我的订单列表 |
| API-SRC-001 | GET | `/api/v1/search/questions` | 题库内关键词搜索 |

> 定时任务命令 5 个已实现（`app/Console/Commands/`）：member:expire-scan / order:close-expired /
> file:clean-temp / file:clean-deleted / data:recount，签名与 `routes/console.php` 调度注册一致。

> 用户后台共 50 个接口已实现，字段级契约见 `../console/API-CONTRACT.md`，前端工程见 `../console/web/`。
> 客户端 P0 接口 27 个已实现（USER/IMP/QUE/WRG/EXM），字段级契约见 `../client/API-CONTRACT.md`。
> 客户端剩余：搜索 API-SRC-001~002（依赖 AI）、会员/订单/支付 API-MBR/ORD/PAY（依赖微信支付商户号）。

---

## 六、定时任务

服务器 crontab 只需一行：

```
* * * * * cd /www/wwwroot/app-server && php artisan schedule:run >> /dev/null 2>&1
```

已注册任务（`routes/console.php`）：会员过期扫描、未支付订单关闭、临时文件清理、软删文件清理、冗余计数校准。

> ⚠️ 这些调度对应的 Artisan 命令（`member:expire-scan` 等）需在后续迭代补齐实现，否则调度会报「命令不存在」。

---

## 七、相关文档

| 文档 | 内容 |
| --- | --- |
| `../docs/01-技术架构与选型.md` | 技术栈、工程目录、页面登记 |
| `../docs/02-代码命名规范.md` | 命名规则（写代码前必读） |
| `../docs/03` / `03A` | 数据库规范 / 数据字典 |
| `../docs/04-API接口规范与登记表.md` | 接口规范与三域台账 |
| `../docs/05-OSS存储与文件分类规范.md` | 存储目录与上传流程 |
| `../docs/06-后台隔离与权限设计.md` | 后台隔离与 RBAC |
| `database/README.md` | 数据库部署与迁移说明 |
