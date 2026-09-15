# 识途刷题 · 多端前端（client）

> 技术栈：uni-app + Vue3 + TypeScript + Pinia + Sass
> 目标端：微信小程序 / Android App（可封装 APK）/ H5
> 规范：命名遵循 `docs/02-代码命名规范.md`，页面清单见 `docs/01-技术架构与选型.md` §7

## 目录结构

```
client/
├── scripts/                  工具脚本（图标生成等）
├── src/
│   ├── api/                  接口层（按模块拆分，编号对应 docs/04 台账）
│   ├── components/           公共组件（easycom 自动注册）
│   ├── mock/                 开发期 Mock 数据（联调后删除）
│   ├── pages/                主包页面（Tab 页 + 登录）
│   ├── pages-sub/            分包页面（业务功能页）
│   ├── stores/               Pinia 状态
│   ├── styles/               设计变量与通用样式
│   ├── types/                全局类型定义
│   └── utils/                请求、存储、平台差异、格式化
├── index.html
├── manifest.json  (src/)
└── pages.json     (src/)
```

## 快速开始

```bash
# 1. 安装依赖（Node 20+）
npm install

# 2. 开发
npm run dev:h5            # H5（浏览器预览）
npm run dev:mp-weixin     # 微信小程序（用微信开发者工具导入 dist/dev/mp-weixin）
npm run dev:app           # App（HBuilderX 导入项目运行）

# 3. 构建
npm run build:h5
npm run build:mp-weixin
npm run build:app

# 4. 类型检查
npm run type-check
```

> **依赖版本说明**：`@dcloudio/*` 各包版本必须完全一致（当前锁定 `3.0.0-4020920240930001`）。
> 若安装失败或版本冲突，执行 `npx @dcloudio/uvm` 对齐到最新稳定版后再构建。

## Android APK 打包（后续直接封装）

1. 用 HBuilderX 打开 `client/` 目录
2. 菜单：发行 → 原生 App-云打包
3. 打包配置已预置在 `src/manifest.json`（权限、ABI：armeabi-v7a / arm64-v8a）
4. 打包前填写 `manifest.json` 中的 `appid`（DCloud AppID）

## 微信小程序配置

1. `src/manifest.json` → `mp-weixin.appid` 填入小程序 AppID
2. 在微信开发者工具中导入 `dist/dev/mp-weixin` 目录
3. 开发阶段可在 `manifest.json` 中临时关闭 `urlCheck`，上线前务必开启并在小程序后台配置 request 合法域名

## Mock 与联调切换

- `src/api/config.ts` 中 `USE_MOCK = true`（默认，使用本地 Mock 数据）
- 后端接口就绪后改为 `false`，业务代码零改动
- 环境地址在 `src/utils/request.ts` 的 `BASE_URL` 中配置

## 设计变量

- 颜色 / 字号 / 间距 / 圆角 / 阴影统一在 `src/styles/variables.scss`
- JS 侧需要的颜色镜像在 `src/styles/tokens.ts`
- **页面样式禁止出现魔法值**，一律引用变量
