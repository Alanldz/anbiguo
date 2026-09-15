import { defineConfig } from 'vite'
import uni from '@dcloudio/vite-plugin-uni'

/**
 * 多端构建配置
 * - H5 / 微信小程序 / App 由 script 选择平台，配置保持一致
 * - 路径别名统一使用 @ 指向 src（见 tsconfig.json）
 */
export default defineConfig({
  plugins: [uni()],
  resolve: {
    alias: {
      '@': '/src'
    }
  },
  css: {
    preprocessorOptions: {
      scss: {
        // 设计变量与通用样式全局注入，页面内无需重复 import
        additionalData: '@import "@/styles/variables.scss";'
      }
    }
  },
  server: {
    port: 5173,
    proxy: {
      // 本地联调代理到后端测试环境，避免跨域
      '/api': {
        target: 'http://api-test.anbiguo.local',
        changeOrigin: true
      }
    }
  }
})
