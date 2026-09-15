// 识途刷题 · 总后台 axios 封装
// 统一请求/响应拦截：注入 token、统一错误提示、10401 跳登录、10403 提示无权限
import axios, { type AxiosInstance, type AxiosRequestConfig, type AxiosResponse } from 'axios'
import { ElMessage, ElMessageBox } from 'element-plus'
import { STORAGE_TOKEN_KEY, CODE_UNAUTHORIZED, CODE_FORBIDDEN, CODE_SUCCESS } from '@/constants'
import type { ApiResponse } from '@/types/api.d'

/** API 基础路径（来自 .env） */
const BASE_URL = import.meta.env.VITE_API_BASE || '/admin-api/v1'

/** 跳登录页（避免循环引用，直接操作 location） */
function redirectToLogin() {
  if (window.location.pathname !== '/login') {
    window.location.href = '/login'
  }
}

const request: AxiosInstance = axios.create({
  baseURL: BASE_URL,
  timeout: 15000,
  headers: {
    'Content-Type': 'application/json',
    'X-Client-Platform': 'admin',
    'X-Client-Version': '1.0.0',
  },
})

// 请求拦截：注入 Bearer token
request.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem(STORAGE_TOKEN_KEY)
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error),
)

// 响应拦截：解包 data、统一错误处理
request.interceptors.response.use(
  (response: AxiosResponse<ApiResponse>) => {
    const body = response.data
    // HTTP 200 但业务码非 0
    if (body && typeof body.code === 'number' && body.code !== CODE_SUCCESS) {
      handleBusinessError(body)
      return Promise.reject(body)
    }
    return response
  },
  (error) => {
    // 网络 / HTTP 层错误
    if (error.response) {
      const body = error.response.data as ApiResponse | undefined
      if (body && typeof body.code === 'number') {
        handleBusinessError(body)
      } else {
        ElMessage.error(error.message || '网络请求失败')
      }
    } else {
      ElMessage.error('网络异常，请稍后重试')
    }
    return Promise.reject(error)
  },
)

function handleBusinessError(body: ApiResponse) {
  switch (body.code) {
    case CODE_UNAUTHORIZED:
      // 未登录：清理 token 并跳登录页
      localStorage.removeItem(STORAGE_TOKEN_KEY)
      ElMessage.error(body.message || '登录已失效，请重新登录')
      redirectToLogin()
      break
    case CODE_FORBIDDEN:
      ElMessage.warning(body.message || '无权限执行该操作')
      break
    default:
      ElMessage.error(body.message || '请求失败')
  }
}

/** 业务请求：自动解包到 data 字段 */
export function http<T = unknown>(config: AxiosRequestConfig): Promise<T> {
  return request(config).then((res) => (res.data as ApiResponse<T>).data)
}

export default request
