// 管理员管理 API（API-ADM-SYS-001）
import { http } from './request'
import type { AdminItem, AdminCreatePayload, AdminUpdatePayload, PaginatedData, ListQuery } from '@/types/api.d'

/** 管理员列表 */
export function fetchAdminList(params: ListQuery) {
  return http<PaginatedData<AdminItem>>('/admins', { method: 'GET', params })
}

/** 新建管理员 */
export function createAdmin(payload: AdminCreatePayload) {
  return http('/admins', { method: 'POST', data: payload })
}

/** 编辑管理员 */
export function updateAdmin(id: number, payload: AdminUpdatePayload) {
  return http(`/admins/${id}`, { method: 'PUT', data: payload })
}

/** 删除管理员 */
export function deleteAdmin(id: number) {
  return http(`/admins/${id}`, { method: 'DELETE' })
}
