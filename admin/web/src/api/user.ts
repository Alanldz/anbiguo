// 用户管理 API（API-ADM-USR-001）
import { http } from './request'
import type { UserItem, UserStatusPayload, PaginatedData, ListQuery } from '@/types/api.d'

/** 用户列表 */
export function fetchUserList(params: ListQuery) {
  return http<PaginatedData<UserItem>>('/users', { method: 'GET', params })
}

/** 用户启停 */
export function updateUserStatus(id: number, payload: UserStatusPayload) {
  return http(`/users/${id}/status`, { method: 'PUT', data: payload })
}
