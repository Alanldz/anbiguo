import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

// 总管理后台独立工程：dev server 端口 8210
// 开发期通过 proxy 将 /admin-api 转发到本地后端（端口 8200）
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    port: 8210,
    host: true,
    proxy: {
      '/admin-api': {
        target: 'http://localhost:8200',
        changeOrigin: true,
      },
    },
  },
  build: {
    outDir: 'dist',
    sourcemap: false,
  },
})
