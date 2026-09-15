# 部署工具包（deploy/）

> 配套 `docs/08-服务器部署指南(宝塔).md` 使用。本目录是**开箱即用**的配置与脚本：
> nginx 站点配置、.env 生产模板、服务器端一键初始化脚本、联调冒烟清单。
> 适用代码版本：三后端（server / admin/api）+ 三前端（client H5 / console/web / admin/web）均已就绪。

## 一、部署拓扑（4 个域名，全部需 ICP 备案）

| 域名（替换成你的） | 站点根目录 | 说明 |
| --- | --- | --- |
| `api.xxx.com` | `server/public` | Laravel 主应用：客户端 API `/api/v1` + 用户后台 API `/console-api/v1` |
| `console.xxx.com` | `console/web/dist` | 用户后台前端静态站；`/console-api/*` 回源到 api 站点 |
| `admin.xxx.com` | `admin/web/dist` | 总后台前端静态站；`/admin-api/*` 回源到 admin/api 应用 |
| `h5.xxx.com` | `client/dist/build/h5` | 手机浏览器版（当前为 Mock 数据演示态） |

> 总后台 API（admin/api）没有独立域名，由 admin 站点的 nginx 反代 `/admin-api/*` 到本机。
> 数据库同一实例 `anbiguo`，三套账号权限隔离（app_client / app_console / app_admin）。

## 二、目录约定（脚本按此路径写死，如不同请全局替换）

```
/www/wwwroot/anbiguo/          # Git 克隆根
├── server/                    # 主应用（api + console-api）
├── admin/api/                 # 总后台应用
├── admin/web/dist/            # 总后台前端构建产物
├── console/web/dist/          # 用户后台前端构建产物
└── client/dist/build/h5/      # H5 构建产物
```

## 三、部署顺序（详细命令见 docs/08，本包只列差异步骤）

1. **环境**：宝塔装 Nginx / MySQL 8.0 / PHP 8.3（扩展+禁用函数清单见 docs/08 §1.2）/ Redis 7（设密码）
2. **代码**：`git clone git@github.com:Alanldz/anbiguo.git /www/wwwroot/anbiguo`（Deploy Key 方式见 docs/08 §3.1）
3. **主应用**：`bash deploy/scripts/server-init.sh server`（或按脚本逐步执行）
4. **总后台**：`bash deploy/scripts/server-init.sh admin`
5. **建库**：导入 `server/sql/` 建库脚本 → 三账号改强密码（docs/08 §3.4）
6. **迁移**：脚本内置（用 app_admin 跑 migrate/seed，完成后 .env 回填 app_client）
7. **站点**：宝塔建 4 个站点 → 分别粘贴 `deploy/nginx/*.conf` 的内容（替换域名与路径）
8. **定时任务**：宝塔「计划任务 → Shell 脚本」加一条：`cd /www/wwwroot/anbiguo/server && php artisan schedule:run >> /dev/null 2>&1`（每分钟）

## 四、.env 关键项核对（两个应用）

| 配置项 | server/.env | admin/api/.env |
| --- | --- | --- |
| APP_KEY | 生成一次 | **必须与 server 完全一致**（sys_configs 密文共解） |
| CACHE_PREFIX | anbiguo_cache | **必须与 server 完全一致**（配置缓存联动） |
| JWT 密钥 | JWT_SECRET_CLIENT / JWT_SECRET_CONSOLE | JWT_SECRET_ADMIN（三者互不相同） |
| DB 账号 | app_client（日常）/ app_admin（迁移） | app_admin |
| APP_DEBUG | false | false |
| SMS_DEBUG_SHOW_CODE | false | — |

## 五、联调冒烟清单（部署完成后逐条 curl 验证）

所有接口响应统一 `{code:0,...}` 为通过；10401=未登录（预期）、10403=越权（预期）。

### 5.1 主应用 api 站点
```bash
# 1. 启动配置（无需登录）
curl https://api.xxx.com/api/v1/config/boot
# 2. 发验证码（日志驱动时验证码在 server/storage/logs/laravel.log）
curl -X POST https://api.xxx.com/api/v1/auth/sms-code -H 'Content-Type: application/json' -d '{"mobile":"13800138000"}'
# 3. 登录拿 token（验证码看日志），后续请求带 -H 'Authorization: Bearer <token>'
curl -X POST https://api.xxx.com/api/v1/auth/login -H 'Content-Type: application/json' -d '{"mobile":"13800138000","sms_code":"123456"}'
# 4. 题库分类 / 我的题库
curl https://api.xxx.com/api/v1/bank-categories -H 'Authorization: Bearer <token>'
curl https://api.xxx.com/api/v1/question-banks -H 'Authorization: Bearer <token>'
# 5. 建题库 → 导入手动题目 → 练习取题 → 提交作答 → 错题列表（P0 主链路）
# 6. 组卷 → 试卷详情 → 交卷（重复交两次验证幂等）→ 考试记录
# 7. 套餐列表 / 我的订单 / 题库内搜索
curl https://api.xxx.com/api/v1/member/plans -H 'Authorization: Bearer <token>'
curl https://api.xxx.com/api/v1/orders -H 'Authorization: Bearer <token>'
# 8. 用户后台 API（同一站点）：登录 → me → 题库列表
curl -X POST https://api.xxx.com/console-api/v1/auth/login -H 'Content-Type: application/json' -d '{"mobile":"13800138000","password":"xxx"}'
curl https://api.xxx.com/console-api/v1/statistics/overview -H 'Authorization: Bearer <console_token>'
```

### 5.2 总后台 admin 站点
```bash
# 登录（账号来自 SystemInitSeeder，见 .env ADMIN_INIT_PASSWORD）
curl -X POST https://admin.xxx.com/admin-api/v1/auth/login -H 'Content-Type: application/json' -d '{"username":"admin","password":"xxx"}'
# 仪表盘 / 管理员列表
curl https://admin.xxx.com/admin-api/v1/dashboard -H 'Authorization: Bearer <admin_token>'
```

### 5.3 前端站点
- 打开 `https://console.xxx.com` → 登录页渲染 → 登录 → 学习概览出数据
- 打开 `https://admin.xxx.com` → 登录页渲染 → 登录 → 仪表盘出数据
- 打开 `https://h5.xxx.com` → H5 首页渲染

## 六、定时任务命令验证

```bash
cd /www/wwwroot/anbiguo/server
php artisan member:expire-scan
php artisan order:close-expired
php artisan file:clean-temp
php artisan file:clean-deleted
php artisan data:recount
```
5 条均输出完成日志即通过（写入 storage/logs/laravel.log）。

## 七、已知待接入（部署不阻塞）

| 事项 | 阻塞资源 |
| --- | --- |
| 导入 AI 解析 / 拍照搜题（SRC-002） | AI 大模型 API Key（总后台配置中心填入即生效） |
| 微信支付回调 | 微信支付商户号 |
| 七牛真实直传 | 七牛 AccessKey（配置中心填入；未填时存储走 local 驱动兜底） |
| 真实短信 | 腾讯云/阿里云短信签名模板（当前日志驱动，验证码落 laravel.log） |
