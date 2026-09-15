# 识途刷题 · 总管理后台前端

品牌：识途刷题 ｜ slogan：用识途，备考路上不走弯路

总管理后台与用户后台**完全独立**：独立工程、独立部署（将部署于 `admin.` 独立域名）。
技术栈：Vue 3.4 + TypeScript + Vite 5 + Element Plus 2.7（全量引入）+ Vue Router 4 + Pinia 2 + Axios。
后端 API 前缀 `/admin-api/v1`，JWT Bearer 认证，guard 独立（`auth:admin`）。

## 一、环境要求

- Node.js ≥ 18
- 包管理器：npm / pnpm 均可

## 二、安装依赖

```bash
npm install
# 或
pnpm install
```

## 三、本地开发

```bash
npm run dev
```

- 开发服务器端口：**8210**（`http://localhost:8210`）
- 请求通过 Vite proxy 转发：`/admin-api` → `http://localhost:8200`
- 本地后端需监听 `8200` 端口，否则接口 404

## 四、构建与预览

```bash
npm run build      # vue-tsc 类型检查 + vite 打包到 dist
npm run preview    # 本地预览构建产物
```

## 五、环境变量（.env）

| 变量 | 说明 |
| --- | --- |
| `VITE_API_BASE` | API 基础路径，见下方两种部署方式 |
| `VITE_APP_TITLE` | 页面标题 |

`.env.development` 已设为 `/admin-api/v1`（配合 Vite proxy）。

## 六、生产部署（两种方案二选一）

### 方案一：独立 API 子域（推荐）

前端直接请求独立 API 域名，修改 `.env.production`：

```
VITE_API_BASE=https://admin-api.yourdomain.com/admin-api/v1
```

构建后把 `dist/` 静态资源托管到任意静态服务器（Nginx / OSS / CDN），
将站点域名（如 `admin.yourdomain.com`）CNAME 到该托管地址即可。

### 方案二：Nginx 反代（前后端同域）

保持 `VITE_API_BASE=/admin-api/v1`，构建并部署 `dist/` 到 Nginx 站点根目录，
由 Nginx 将 `/admin-api` 反代到后端服务：

```nginx
server {
    listen 80;
    server_name admin.yourdomain.com;

    location / {
        root /path/to/dist;
        try_files $uri $uri/ /index.html;
    }

    location /admin-api/ {
        proxy_pass http://127.0.0.1:8200;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

## 七、目录结构

```
src/
├── api/          # 按业务域拆分接口（request.ts 为 axios 封装）
├── components/   # 公共组件（按需）
├── constants.ts  # 状态映射等常量集中管理
├── layout/       # AdminLayout 后台框架（侧边栏/顶栏）
├── router/       # 路由表 + 登录守卫
├── stores/       # Pinia：auth（token/管理员/权限）
├── styles/       # 全局样式（Element 主题主色 #2563EB）
├── types/        # api.d.ts 契约 TypeScript 类型
└── views/        # 页面：login/dashboard/system/user/bank/content
```
