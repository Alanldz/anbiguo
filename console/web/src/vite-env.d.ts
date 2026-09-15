/// <reference types="vite/client" />

// Vite 环境变量类型（.env.development / .env.production）
interface ImportMetaEnv {
  /** API 基础路径，如 /console-api/v1 */
  readonly VITE_API_BASE: string
  /** 站点标题 */
  readonly VITE_APP_TITLE: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
