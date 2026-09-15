// 认证模块 API（API-CSL-AUTH-001/002/003/004）
import { http } from './request'
import type { UserBrief, LoginData, SmsCodeData } from '@/types/api.d'

/** API-CSL-AUTH-001 登录 */
export function login(payload: {
  mobile: string
  password?: string
  code?: string
  login_type: number
}) {
  return http<LoginData>('/auth/login', { method: 'POST', data: payload })
}

/** API-CSL-AUTH-002 登出 */
export function logout() {
  return http('/auth/logout', { method: 'POST' })
}

/** API-CSL-AUTH-003 获取当前用户信息 */
export function fetchMe() {
  return http<UserBrief>('/auth/me', { method: 'GET' })
}

/** API-CSL-AUTH-004 发送短信验证码 */
export function sendSmsCode(payload: { mobile: string; scene?: string }) {
  return http<SmsCodeData>('/auth/sms-code', { method: 'POST', data: payload })
}
