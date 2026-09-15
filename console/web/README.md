# 识途刷题 · 用户电脑端后台（console/web）

> 面向普通用户（C 端）的电脑浏览器后台，管理**自己的**题库、题目、导入任务、学习资料、错题、考试记录、订单与账号。
> 与总管理后台 `admin/web/` 是**两个完全独立的工程**：目录、端口、token、登录态互不相通。

## 技术栈

Vue 3.4 + TypeScript + Vite 5 + Element Plus 2.7 + Pinia + Vue Router + Axios

## 快速开始

```bash
npm install
npm run dev      # http://localhost:8230（proxy /console-api → http://localhost:8000）
npm run build    # vue-tsc --noEmit && vite build
npm run preview
```

> 后端为 `../server/`（`php artisan serve` 默认 8000 端口），需先启动后端并配置
> `JWT_SECRET_CONSOLE`（见 `server/.env.example`）才能登录成功。

## 环境变量

| 文件 | 变量 | 说明 |
| --- | --- | --- |
| `.env.development` | `VITE_API_BASE=/console-api/v1` | 走 vite proxy |
| `.env.production` | `VITE_API_BASE=https://console.anbiguo.com/console-api/v1` | 生产域名占位 |

## 与其他端的隔离约定（docs/06 §二）

| 项 | 值 |
| --- | --- |
| 路由前缀 | `/console-api/v1` |
| 守卫 | `auth:console`（JWT，2 小时，不刷新） |
| Token 存储 key | `shitu_console_token`（localStorage） |
| 平台标识 | `X-Client-Platform: console` |
| dev 端口 | 8230（admin/web 为 8210，客户端 H5 为 5173） |

## 目录结构

```
console/web/src/
├── api/            # 11 个模块，每个函数对应 docs/04 §四 的接口编号
├── layout/         # ConsoleLayout（侧边菜单 + 顶栏）
├── router/         # 路由表 + 登录守卫 + 权限码 meta
├── stores/         # auth（token + 当前用户）
├── styles/         # 浅色主题基础样式
├── types/          # 与 console/API-CONTRACT.md 一一对应的 TS 类型
└── views/          # 10 个页面
    ├── login/        # 密码 / 验证码双 tab 登录
    ├── dashboard/    # 学习概览（统计卡 + 近 30 天趋势 SVG 图）
    ├── banks/        # 我的题库（CRUD / 导出）
    ├── questions/    # 题目管理（章节 + 题目 + 批量操作）
    ├── import/       # 题库导入（任务列表 + 进度）
    ├── resources/    # 学习资料（分类树 + 文件）
    ├── wrong/        # 我的错题
    ├── exams/        # 考试记录（含作答明细抽屉）
    ├── orders/       # 订单与会员
    └── account/      # 账号设置（资料 / 密码 / 换绑手机）
```

## 页面权限码（菜单显隐用；后端以「数据归属」为硬边界）

| 路径 | 权限码 |
| --- | --- |
| `/dashboard` | `console:dashboard` |
| `/banks` | `console:bank:list` |
| `/banks/:id/questions` | `console:question:list` |
| `/import` | `console:import:create` |
| `/resources` | `console:file:list` |
| `/wrong` | `console:wrong:list` |
| `/exams` | `console:exam:list` |
| `/orders` | `console:order:list` |
| `/account` | `console:account:view` |

## 接口契约

字段级契约见 `../console/API-CONTRACT.md`——**改字段先改契约，再改前后端**。

## 已知 TODO

- 七牛直传未接入：导入/资料上传当前取文件元信息后直接调登记接口，正式流程
  （`POST /files/upload-token` 取凭证 → 前端直传七牛 → `POST /file-assets` 登记）
  待七牛账号开通后在 `views/import`、`views/resources` 中按 TODO 注释补齐
- 学习概览趋势图为轻量 SVG 实现，后续如需更丰富图表可引入 echarts
