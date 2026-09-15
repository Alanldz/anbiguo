# CHANGELOG · 文档与规范变更记录

> 记录粒度：文档规范层面的变更。代码变更走 Git 提交记录。
> 格式：`日期 · 变更人 · 变更内容 · 影响范围`

## 2026-09-15 · 第十二次变更

**变更人**：WorkBuddy（待补充实际负责人）

**变更内容**

1. **P0 补漏 6 接口落地**：API-FAV-001 收藏列表、API-NOTE-001 笔记列表、API-REC-001 练习记录列表、
   API-USER-004 账号注销（置 CANCELING + 吊销 Token，合规必备）、API-BANK-008/009 回收站列表与恢复。
   契约见 `client/API-CONTRACT.md` §十。
2. **客户端页面激活**：favorite/list、note/list、record/list、setting（退出/注销）由占位页转真实实现；
   新增回收站页 `pages-sub/bank/recycle`，入口挂 mine 页。客户端占位页由 17 个降至 12 个。
3. **台账进度**：133 个接口已开发 130 个（§八同步）。

**影响范围**：server（路由/服务/控制器/错误码）、client（页面/API/Mock/类型）、docs/04、契约文档

## 2026-09-15 · 第十一次变更

**变更人**：WorkBuddy（待补充实际负责人）

**变更内容**

1. **总后台 P2 六接口落地（API-ADM-100~105）**：分类管理（题库/资料双 type）、配置连通测试（TCP 探测 ≤5s）、
   订单管理/退款（仅记录状态，资金退回待微信支付接入）、文件资源管理（软删记录）、意见反馈处理、会员套餐配置。
2. **新增表**：`sys_feedbacks`（migration `2026_09_15_100001`，此前台账有 ADM-104 但表缺失，本次补齐）。
3. **admin/web P2 页面**：分类管理（树形）、订单与会员套餐（Tabs）、文件管理、意见反馈、配置页「测试连通」按钮；
   权限码与 SystemInitSeeder 完全一致。vue-tsc + vite 构建零错误。
4. **台账对账**：docs/04 §五 33 条全部「已实现」；§八进度更新为 **127 个接口已开发 124 个**，剩余仅
   API-SRC-002（AI）、API-PAY-001~002（微信支付）3 个，均被外部账号阻塞。

**影响范围**：admin/api、admin/web、server/database/migrations（仅新增）、docs/04、docs/06

## 2026-09-15 · 第十次变更

**变更人**：WorkBuddy（待补充实际负责人）

**变更内容**

1. **部署阶段启动**：新增部署工具包 `deploy/`，配合 docs/08 使用：
   - `deploy/README.md`：四域名部署拓扑（api / console / admin / h5）、目录约定、.env 核对表、
     **联调冒烟清单**（主链路逐条 curl 用例：启动配置→登录→题库→练习→考试幂等→订单→双后台）
   - `deploy/nginx/{api,console,admin,h5}.conf`：4 个站点配置——api 站点跑主应用；
     console/admin 为静态前端 + `/console-api`、`/admin-api` 本机反代（admin/api 走 127.0.0.1:81 专用入口，不对外）；
     h5 为静态站预留 `/api` 同域反代
   - `deploy/scripts/server-init.sh`：服务器端初始化脚本（composer install / .env 检查 / migrate+seed /
     storage:link / config+route 缓存 / 权限），server 与 admin 两个模式
2. **docs/08** 顶部新增 deploy/ 快捷方式指引；**三个前端生产构建验证通过**
   （admin/web、console/web 走 vue-tsc + vite build；client H5 走 uni build，产物 `client/dist/build/h5`）

**影响范围**：部署交付物新增 `deploy/` 目录；文档导航更新。

## 2026-09-15 · 第九次变更

**变更人**：WorkBuddy（待补充实际负责人）

**变更内容**

1. **客户端 P1 接口（无第三方依赖部分）4 个落地**：
   - API-MBR-001 会员套餐列表、API-MBR-002 开通会员下单（本期仅创建待支付订单，支付待微信支付接入）、
     API-ORD-001 我的订单列表、API-SRC-001 题库内关键词搜索（%/_ 转义 + 题库可见性校验）
   - 契约 `client/API-CONTRACT.md` 新增 §七~§九；新增 Member/Order/Search 控制器与服务
2. **5 个定时任务 Artisan 命令实现**（`app/Console/Commands/`，签名与 `routes/console.php` 调度注册逐一核对一致）：
   member:expire-scan / order:close-expired / file:clean-temp / file:clean-deleted / data:recount，
   全部 chunkById 分块 + Log 记录 + handle 返回码
3. **`docs/04` 进度更新**：121 个接口已开发 112 个（剩余：API-SRC-002 依赖 AI、API-PAY-001~002 依赖微信支付商户号、API-ADM-100~105 总后台 P2）

**影响范围**：客户端商业化查询链路、搜索、定时任务（`schedule:run` 不再报命令不存在）。

## 2026-09-15 · 第八次变更

**变更人**：WorkBuddy（待补充实际负责人）

**变更内容**

1. **客户端 P0 接口 27 个全部落地**（导入 / 练习 / 错题 / 考试 / 用户）：
   - 新增字段级契约 `client/API-CONTRACT.md`，响应字段严格对齐 `client/src/types/index.ts` 与 Mock 数据形状
   - 新增 `Api/V1` 控制器 5 个（Profile/Import/QuestionPractice/WrongQuestion/Exam）+ `Services/Api/` 服务 5 个，
     `routes/client.php` 待开发区替换为真实路由（27 条，`auth:client + user.active`）
   - 交卷幂等（重复提交返回已有成绩）；答错自动入错题本（upsert 累计）并更新 `user_daily_stats`
   - 导入与 OCR 为**同步占位实现**（建 `question_import_tasks`，status=待校对，result_json 标注 pending），
     待 AI 大模型账号接入后升级为真实解析
2. **`docs/04` 台账全面校准**：§二 客户端登记表状态列与 §八 实况对齐；
   §八 补登 27 行落地文件；进度更新为 121 个接口已开发 113 个（剩余：搜索 2、会员/订单/支付 5、总后台 6）

**影响范围**：客户端全部 P0 模块、接口台账（§二状态列批量修正为破坏性可见变更）。

## 2026-09-15 · 第七次变更

**变更人**：WorkBuddy（待补充实际负责人）

**变更内容**

1. **用户电脑端后台（console）落地**——三端中的第二端完成：
   - **接口契约先行**：新增 `console/API-CONTRACT.md`（字段级契约），定义 50 个接口的路径/入参/响应字段/枚举文本映射，
     作为前后端并行开发的唯一对接依据，任何字段增删先改契约再改代码
   - **后端**（在 `server/` 内扩展，与客户端共用一个 Laravel 应用，符合 docs/06 §二既定隔离方案）：
     新增 `Console/V1` 控制器 11 个 + `Services/Console/` 服务 9 个，重写 `routes/console_api.php`，
     全部挂 `auth:console + console.user`，数据一律以当前用户为边界（越权抛 FORBIDDEN）；
     编号扩展为 API-CSL-AUTH/STAT/BANK/CHP/QST/IMP/FIL/WRG/EXM/ORD/ACC 共 50 个并全部实现
   - **前端**：新增 `console/web/` 独立工程（Vue3 + TS + Element Plus + Pinia，dev 端口 8230，
     proxy `/console-api` → `:8000`，token key `shitu_console_token`，`X-Client-Platform: console`），
     与总后台 admin/web 完全隔离、登录态互不相通；
     10 个页面：登录 / 学习概览 / 我的题库 / 题目管理 / 题库导入 / 学习资料 / 我的错题 / 考试记录 / 订单与会员 / 账号设置；
     `npm run build`（vue-tsc + vite）零错误通过
2. **`docs/04` §四重排**：用户后台台账按契约编号重排为 50 条并全部标记已实现
   （原 CSL-BANK-003「批量导入」重排为 CSL-IMP-*）；§八进度更新为 67/87
3. **`docs/06` §九 页面登记**：console 端 10 个页面标记已实现并补充实现登记注记
4. **`server/README.md` §五**：用户后台接口清单同步更新

**影响范围**：用户后台全模块、接口台账（编号重排为破坏性变更）、前端工程目录新增 console/。

**遗留与下一步**

- 导入任务为同步占位实现（状态置「待校对」），AI/文档真实解析待 EXT-AI-001 接入后迭代
- 七牛直传前端流程（取凭证 → 直传 → 登记）在 console/web 中留有 TODO，待七牛账号开通后联调
- 本机无 PHP/MySQL 环境，console 后端未经过运行时验证，部署后需接口冒烟测试

---

## 2026-09-15 · 第六次变更

**变更人**：WorkBuddy（待补充实际负责人）

**变更内容**

1. **总管理后台落地**（docs/06 物理隔离方案的第一次完整实现）：
   - 后端 `admin/api/`：Laravel 11 独立应用（55 个 PHP 文件），27 个接口已实现
     （认证 3 / 仪表盘 1 / 管理员与角色权限 9 / 配置中心 2 / 日志 2 / 用户 2 / 题库审核 3 / 轮播 4 / 导题监控 1），
     编号 API-ADM-001~090，另登记待开发 API-ADM-100~105
   - 前端 `admin/web/`：Vue3 + TS + Element Plus 独立工程（登录/布局/仪表盘/11 个业务页面），
     菜单权限码与 SystemInitSeeder 的 sys_permissions.code 对齐
   - 隔离三要点写入部署文档：APP_KEY 与主应用一致（密文共享解密）、CACHE_PREFIX 一致（配置缓存联动）、
     JWT_SECRET_ADMIN 独立（scp=admin 防跨端）
2. **`docs/04` §五 重排**：总后台接口台账改为实现编号 API-ADM-0xx，逐条标注状态与落地路径
3. **`docs/06` §九 页面登记**：admin 端 11 个页面标记已实现，页面路径/权限码按实际工程更新
4. **`docs/01` 目录树 / 根 README**：新增 admin/ 目录说明
5. **`docs/08` 新增 §4.6**：总后台前后端部署步骤与三个易错点

**影响范围**：总后台全模块、接口台账、部署文档。

## 2026-09-15 · 第五次变更

**变更人**：WorkBuddy（待补充实际负责人）

**变更内容**

1. **品牌定名「识途」**：口号「用识途，备考路上不走弯路」。全项目 12 处用户可见文案由「刷题平台/安必果刷题/安比果」统一替换为「识途刷题」，覆盖 client（manifest/pages.json/login/mine/题库详情）、server（.env.example/config/seeder/ConfigController/composer.json）与 README。**内部标识暂未改**：目录名 `anbiguo`、包名 `anbiguo-client`、`anbiguo/server`、域名占位 `api.anbiguo.com`——待正式域名确定后统一处理。
2. **新增 `docs/08-服务器部署指南(宝塔).md`**：宝塔环境清单（Nginx/MySQL8/PHP8.3+扩展+禁用函数/Redis/Supervisor）、域名与四站点规划、代码部署五步、建库与三账号隔离验证、Nginx 配置、队列与计划任务、七类第三方服务开通清单（七牛/腾讯云短信/小程序/微信支付/AI 大模型/OCR/SSL）与 sys_configs 配置键对照、安全加固清单、当前代码缺口与执行顺序。
3. **GitHub 仓库建立**：`git@github.com:Alanldz/anbiguo.git`（Private），main 已推送；根 `.gitignore` 与根 `README.md` 新增。

**影响范围**：全部端文案展示、部署运维流程、代码仓库。

## 2026-09-15 · 第四次变更

**变更人**：WorkBuddy（待补充实际负责人）

**变更内容**

1. **第三步后端工程落地（`server/`，Laravel 11）**：完整的可运行工程骨架，而非零散代码片段。
   - 分层：Controller（只取参与返回）→ Service（业务/事务/权限）→ Model（数据映射）
   - 基础设施：统一响应 `ApiResponse`（含分页结构）、错误码中心 `ErrorCode`（1xxxx~9xxxx 完整清单）、
     业务异常 `BusinessException`、链路追踪 `RequestContext` + `RequestIdMiddleware`
   - 全局异常映射：业务异常/校验失败/未登录/越权/限流/404/405/兜底，全部转为统一 JSON，不再返回 HTML
   - 鉴权：自研 `JwtGuard`，三端三密钥 + `scp` 作用域，**Token 跨端直接 10401**；退出登录走 jti 黑名单
   - 配置中心 `ConfigCenter`：读 `sys_configs`，敏感项 AES 解密，Redis 二级缓存，界面脱敏
   - 存储抽象层 `StorageService` + `StorageDriverInterface` + `QiniuDriver`/`LocalDriver` + `ObjectKeyGenerator`
     （按 `docs/05` 目录规范生成 object_key，扩展名白名单硬校验）
   - 枚举层 29 个 BackedEnum（含 `label()` / `labelMap()` / `isValid()`），全项目杜绝魔法数字
   - 中间件：链路ID、强制 JSON、平台标识、客户端/后台账号状态校验、后台操作日志（含敏感字段脱敏）
   - 限流：短信按手机号（60s/次 + 10次/天）、登录按手机号（10分钟5次）、通用接口、AI 重资源
2. **首批垂直切片 20 个接口已开发**（占台账 46 个中的 43%）：
   客户端认证 5 个 + 启动配置 1 个 + 题库与分类 7 个 + 文件直传 4 个 + 用户后台认证 3 个。
3. **`docs/04` 新增 §八 实现进度登记**：逐接口登记状态与落地文件，附「待补齐清单」与
   「尚未实现的服务端能力」两节，让台账与代码始终保持可核对。
4. **`docs/01` §三 工程目录结构细化**：`server/` 目录树更新为实际落地结构。
5. **`server/README.md`**：分层约定、三端鉴权表、部署五步、已实现接口清单、定时任务说明。

**影响范围**

- 后端新增代码必须遵循「控制器不写业务、返回必过 ApiResponse、失败必抛 BusinessException」三条红线
- 新增枚举值必须同步 `app/Enums/` 与 `docs/03` §三
- 新增接口必须先登记 `docs/04` §三 编号，实现后回填 §八 进度
- 第三方 KEY 一律走 `ConfigCenter`，禁止 `env()` 直读

**遗留与下一步**

- 定时任务调度已注册（`routes/console.php`）但 5 个 Artisan 命令未实现，需尽快补齐，否则 `schedule:run` 报错
- 腾讯云短信驱动、微信支付、AI 导题解析、OCR 均为未实现状态，已在 `docs/04` §8.2 登记
- 本机无 PHP/Composer/MySQL 环境，源码未经过运行时验证，装好环境后需先跑 `composer install` 与接口冒烟测试

---

## 2026-09-15 · 第三次变更

**变更人**：WorkBuddy（待补充实际负责人）

**变更内容**

1. **第二步数据库设计完成（33 张表）**，migration 落在 `server/database/migrations/`，按业务域分 8 个文件：
   用户域 8 / 题库域 3 / 题目域 4 / 考试域 4 / 交易域 3 / 文件域 2 / 内容域 1 / 系统域 8。
2. **新增 `docs/03A-数据字典.md`**：33 张表字段级明细（字段 / 类型 / 允许空 / 默认 / 说明 / 索引），
   `03` 保留命名规范、表清单总览、枚举登记与变更登记，两者职责分离。
3. **`03` 新增 `content_` 前缀（内容 / 运营域）**：客户端需读取的表（如 Banner）不得用 `sys_` 前缀，
   因为 `sys_` 表对客户端数据库账号零授权。
4. **表结构调整（相对原清单）**：
   - `question_notes` → **`user_question_notes`**（归入用户域，笔记是用户私有数据）
   - 新增 `user_daily_stats`、`question_reports`、`question_import_tasks`、`exam_paper_questions`、
     `order_member_plans`、`content_banners`
   - `sys_roles`（原「角色权限表」）拆为 `sys_roles` / `sys_permissions` / `sys_role_permissions` / `sys_admin_roles`
5. **数据初始化 seeder**：`SystemInitSeeder`（33 个权限点 + 4 个预置角色 + 初始管理员 + 33 项配置中心配置）
   与 `BusinessInitSeeder`（14 个题库分类 + 6 个文件预置分类 + 3 个会员套餐 + 2 个运营位），均为幂等。
6. **数据库隔离脚本**：`sql/00_create_database.sql`（建库）、`sql/01_create_users_and_privileges.sql`
   （`app_client` / `app_console` / `app_admin` 三套账号 + sys_ 表零授权 + 三条校验查询）。
7. **`server/database/README.md`**：首次部署六步执行顺序、常用命令、新增表标准动作、字段约定速查。

**影响范围**

- 后端开发前必须先执行 `00_create_database.sql` → `migrate` → `db:seed` → `01_create_users_and_privileges.sql`
- 客户端与用户后台的 `.env` 必须使用 `app_client` / `app_console` 账号（无 DDL 权限）
- 新增表必须同步更新 `03A` 字段明细 + `03` 表清单 + `03` 变更登记

---

## 2026-09-15 · 第二次变更

**变更人**：WorkBuddy（待补充实际负责人）

**变更内容**

1. **技术决策拍板并落档**：前端 uni-app + Vue3 + TS（D-01）、后端 Laravel 11（D-02）、对象存储七牛云 Kodo（D-03）、Android 用 HBuilderX 云打包（D-04）、双后台 Vue3 + Element Plus（D-05）。同步更新 `00`、`01`、`04`、`05` 四份文档。
2. **`01` 新增「页面清单与路由登记表」**：主包 6 页（P-01~P-06）+ 分包 21 页（P-07~P-27）+ 公共组件 9 个（C-01~C-09），新增页面必须在此登记。
3. **`04` 新增存储抽象层约定**：业务代码只调 `StorageService`，底层七牛/阿里云可切换，`storage.provider` 决定驱动。
4. **`05` 上传流程改为七牛方案**：后端签发 uploadToken（限定前缀+时效）→ 前端直传 → 回调登记，私有空走签名 URL。
5. **第一步前端工程落地**（`client/`）：
   - 工程骨架：package.json / vite.config / tsconfig / manifest / pages.json（5 Tab + 21 分包路由全部登记）
   - 设计系统：`styles/variables.scss` 设计变量 + `styles/common.scss` 通用样式 + `styles/tokens.ts` JS 侧镜像
   - 基础层：`utils/request.ts`（统一请求/鉴权/错误码）、`utils/storage.ts`、`utils/platform/`（多端差异抽象）、`utils/format.ts`、`types/index.ts`
   - 接口层：api/auth、user、bank、question、exam、importer、file，与 docs/04 台账编号一一对应，`USE_MOCK` 一键切换 Mock/真实接口
   - 公共组件 9 个全部完成
   - 页面：P-01~P-07、P-09、P-16、P-19 已完成实现；其余分包页为「占位就绪」（统一使用 `base-dev-placeholder`，规划功能已写入页面）
   - tabBar 图标 10 张由 `scripts/gen-tabbar-icons.py` 生成（PyMuPDF 矢量绘制）
   - `client/README.md`：安装、多端构建、APK 打包、Mock 切换说明

**影响范围**

- 前端后续开发必须引用设计变量，禁止魔法值
- 新页面必须先在 `01` §7 登记，再建文件
- Mock 切换只改 `src/api/config.ts`，其余不动

---

## 2026-09-15 · 初始化

**变更人**：WorkBuddy（待补充实际负责人）

**变更内容**

1. 建立 `docs/` 开发文档体系（共 8 份文档 + 本变更日志）：
   - `README.md` 文档索引与维护规则
   - `00-项目总览与开发路线.md` 五步开发路线（含前置第 0 步）
   - `01-技术架构与选型.md` 多端方案与工程目录结构
   - `02-代码命名规范.md` 前端/后端/数据库/Git 命名规则
   - `03-数据库设计规范.md` 命名规范 + 数据字典登记表 + 枚举登记
   - `04-API接口规范与登记表.md` 三域接口规范 + 接口台账 + 第三方接口登记
   - `05-OSS存储与文件分类规范.md` 目录分类、命名、上传流程、清理策略
   - `06-后台隔离与权限设计.md` 总后台完全独立方案 + RBAC + 安全加固
   - `07-项目管理与协作规范.md` 分支、提交、评审、发布、文档同步
2. 建立五步开发路线的任务追踪（第 0~5 步）

**影响范围**

- 后续所有代码必须遵循 `02` 命名规范
- 所有建表必须登记 `03` 数据字典
- 所有接口必须登记 `04` 台账
- 所有上传文件必须遵循 `05` 目录分类
- 总后台必须按 `06` 完全独立部署

**待确认决策（影响后续架构，需尽快拍板）**

| 编号 | 决策项 | 建议 |
| --- | --- | --- |
| D-01 | 前端多端框架 | uni-app + Vue3 |
| D-02 | PHP 框架 | Laravel 11 / ThinkPHP 8 |
| D-03 | OSS 服务商 | 阿里云 OSS |
| D-04 | Android 封装方式 | HBuilderX 云打包 |
| D-05 | 用户后台技术栈 | Vue3 + Element Plus |
