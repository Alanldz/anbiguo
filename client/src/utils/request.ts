/**
 * 请求封装（全局唯一出口）
 * 规则：页面与组件禁止直接调用 uni.request，一律通过 src/api/ 下的方法调用本模块。
 * 职责：拼装公共请求头、统一鉴权、统一错误码处理、统一 loading。
 * 规范详见 docs/04-API接口规范与登记表.md
 */

import type { ApiResponse, PageData } from '@/types'
import { getStorage, removeStorage, STORAGE_KEYS } from '@/utils/storage'
import { getClientPlatform, getClientVersion } from '@/utils/platform'

/** 后端接口基础地址，按环境区分（与 docs/07 §四 环境表对应） */
const BASE_URL = {
  development: 'http://api-test.anbiguo.local',
  production: 'https://api.anbiguo.com'
} as const

const ENV = (import.meta.env.MODE === 'production' ? 'production' : 'development') as keyof typeof BASE_URL

/** 业务错误码：登录失效需跳登录 */
const CODE_UNAUTHORIZED = 10401

interface RequestOptions {
  url: string
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE'
  data?: Record<string, unknown>
  /** 是否显示全局 loading，默认 false（列表类请求不建议开启） */
  loading?: boolean
  /** 是否自动携带 Token，默认 true */
  auth?: boolean
  /** 是否直接返回完整响应体（含 code/message），默认 false 仅返回 data */
  raw?: boolean
}

function generateRequestId(): string {
  return `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`
}

function buildHeader(options: RequestOptions): Record<string, string> {
  const header: Record<string, string> = {
    'Content-Type': 'application/json',
    'X-Client-Platform': getClientPlatform(),
    'X-Client-Version': getClientVersion(),
    'X-Request-Id': generateRequestId()
  }
  if (options.auth !== false) {
    const token = getStorage<string>(STORAGE_KEYS.TOKEN)
    if (token) header.Authorization = `Bearer ${token}`
  }
  return header
}

/** 登录失效统一处理：清登录态 + 跳登录页 */
function handleUnauthorized(): void {
  removeStorage(STORAGE_KEYS.TOKEN)
  removeStorage(STORAGE_KEYS.USER_PROFILE)
  uni.navigateTo({ url: '/pages/auth/login' })
}

export function request<T = unknown>(options: RequestOptions): Promise<T> {
  const { url, method = 'GET', data, loading = false, raw = false } = options

  if (loading) uni.showLoading({ title: '加载中', mask: true })

  return new Promise<T>((resolve, reject) => {
    uni.request({
      url: BASE_URL[ENV] + url,
      method,
      data,
      header: buildHeader(options),
      success: (res) => {
        const body = res.data as ApiResponse<T>

        if (body.code === 0) {
          resolve((raw ? body : body.data) as T)
          return
        }

        if (body.code === CODE_UNAUTHORIZED) {
          handleUnauthorized()
        }
        uni.showToast({ title: body.message || '请求失败', icon: 'none' })
        reject(body)
      },
      fail: (err) => {
        uni.showToast({ title: '网络异常，请稍后重试', icon: 'none' })
        reject(err)
      },
      complete: () => {
        if (loading) uni.hideLoading()
      }
    })
  })
}

export const http = {
  get: <T>(url: string, params?: Record<string, unknown>, extra?: Partial<RequestOptions>) =>
    request<T>({ url: withQuery(url, params), method: 'GET', ...extra }),
  post: <T>(url: string, data?: Record<string, unknown>, extra?: Partial<RequestOptions>) =>
    request<T>({ url, method: 'POST', data, ...extra }),
  put: <T>(url: string, data?: Record<string, unknown>, extra?: Partial<RequestOptions>) =>
    request<T>({ url, method: 'PUT', data, ...extra }),
  del: <T>(url: string, data?: Record<string, unknown>, extra?: Partial<RequestOptions>) =>
    request<T>({ url, method: 'DELETE', data, ...extra })
}

/** 拼接 query（GET 请求使用） */
function withQuery(url: string, params?: Record<string, unknown>): string {
  if (!params) return url
  const query = Object.entries(params)
    .filter(([, v]) => v !== undefined && v !== null && v !== '')
    .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(String(v))}`)
    .join('&')
  return query ? `${url}${url.includes('?') ? '&' : '?'}${query}` : url
}

/** 分页请求的便捷方法 */
export function requestPage<T>(
  url: string,
  params?: Record<string, unknown>
): Promise<PageData<T>> {
  return http.get<PageData<T>>(url, params)
}
