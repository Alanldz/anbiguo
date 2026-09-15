// 账号设置 API（API-CSL-ACC-001 ~ 004）
import { http } from './request'
import type {
  ProfileInfo,
  ProfileUpdatePayload,
  PasswordUpdatePayload,
  MobileUpdatePayload,
} from '@/types/api.d'

/** API-CSL-ACC-001 个人资料 */
export function fetchProfile() {
  return http<ProfileInfo>('/account/profile', { method: 'GET' })
}

/** API-CSL-ACC-002 更新资料 */
export function updateProfile(payload: ProfileUpdatePayload) {
  return http('/account/profile', { method: 'PUT', data: payload })
}

/** API-CSL-ACC-003 修改密码 */
export function updatePassword(payload: PasswordUpdatePayload) {
  return http('/account/password', { method: 'PUT', data: payload })
}

/** API-CSL-ACC-004 换绑手机 */
export function updateMobile(payload: MobileUpdatePayload) {
  return http('/account/mobile', { method: 'PUT', data: payload })
}
