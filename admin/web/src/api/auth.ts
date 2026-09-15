// 认证模块 API（API-ADM-AUTH-001/002、/me）
import { http } from './request'
import type { AdminProfile, LoginData } from '@/types/api.d'

/** 管理员登录 */
export function login(username: string, password: string) {
  return http<LoginData>('/auth/login', { method: 'POST', data: { username, password } })
}

/** 管理员登出 */
export function logout() {
  return http('/auth/logout', { method: 'POST' })
}

/** 获取当前管理员信息 */
export function fetchMe() {
  return http<AdminProfile>('/auth/me', { method: 'GET' })
}
