# 识途刷题

> **用识途，备考路上不走弯路。**

一款多端在线刷题平台：用户上传文档即可 AI 生成题库，支持顺序/专项练习、模拟考试、错题本、闪卡记忆、会员体系与创作者生态。

## 技术栈

| 端 | 技术 | 说明 |
| --- | --- | --- |
| 客户端 | uni-app + Vue3 + TypeScript | 一套代码编译微信小程序 / Android APK / H5 |
| 后端 | PHP 8.3 + Laravel 11 | 前后端严格分离，三域接口 |
| 数据库 | MySQL 8.0 + Redis 7 | utf8mb4 / InnoDB，33 张业务表 |
| 对象存储 | 七牛云 Kodo（抽象层可切换） | 文档归类存储、前端直传 |
| 双后台 | Vue3 + Element Plus | 用户后台与总后台完全隔离部署 |

## 仓库结构

```
├── docs/     # 开发文档体系（规范唯一权威来源，共 11 份）
├── client/   # 多端前端（uni-app，微信小程序 / Android / H5）
├── server/   # PHP 后端（Laravel 11，migration + 模型 + 接口）
├── admin/    # 系统总后台（api/：Laravel 独立应用；web/：Vue3 + Element Plus 独立工程）
└── 刷题软件功能清单.md   # 产品功能清单（15 模块 / 约 90 项，P0-P2）
```

## 开发文档

一切约定以 [`docs/`](./docs/README.md) 为准：命名规范、数据库规范与数据字典、API 台账、OSS 分类规范、后台隔离设计、协作规范。

## 本地运行（前端 H5 演示）

```bash
cd client
npm install
npm run dev:h5     # http://localhost:5173/（Mock 数据模式）
```

后端部署与接口说明见 [`server/README.md`](./server/README.md)。

## 约定

- 提交信息格式、分支策略见 `docs/07-项目管理与协作规范.md`
- 新增接口必须先在 `docs/04` 台账登记；新建表必须回填 `docs/03A` 数据字典
