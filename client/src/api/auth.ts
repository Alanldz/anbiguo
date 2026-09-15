/**
 * 认证相关接口
 * 接口编号见 docs/04-API接口规范与登记表.md §三
 */

import { http } from '@/utils/request'
import { USE_MOCK, mockDelay } from './config'
import { mockUserProfile } from '@/mock'
import type { LoginResult } from '@/types'

/** API-AUTH-001 发送短信验证码 */
export function sendSmsCode(mobile: string): Promise<void> {
  if (USE_MOCK) return mockDelay(undefined as void)
  return http.post<void>('/api/v1/auth/sms-code', { mobile })
}

/** API-AUTH-002 手机号登录 / 注册 */
export function loginByMobile(mobile: string, code: string): Promise<LoginResult> {
  if (USE_MOCK) {
    return mockDelay({ token: 'mock-token', expires_in: 604800, user: mockUserProfile })
  }
  return http.post<LoginResult>('/api/v1/auth/login', { mobile, code })
}

/** API-AUTH-003 微信小程序登录 */
export function loginByWechat(code: string): Promise<LoginResult> {
  if (USE_MOCK) {
    return mockDelay({ token: 'mock-token', expires_in: 604800, user: mockUserProfile })
  }
  return http.post<LoginResult>('/api/v1/auth/wechat-login', { code })
}

/** API-AUTH-004 刷新 Token */
export function refreshToken(): Promise<{ token: string; expires_in: number }> {
  return http.post<{ token: string; expires_in: number }>('/api/v1/auth/refresh')
}
