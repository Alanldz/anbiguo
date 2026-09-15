// 角色与权限 API（API-ADM-SYS-001 角色部分、权限树）
import { http } from './request'
import type { RoleItem, RolePayload, PermissionNode } from '@/types/api.d'

/** 角色列表 */
export function fetchRoleList() {
  return http<RoleItem[]>('/roles', { method: 'GET' })
}

/** 新建角色 */
export function createRole(payload: RolePayload) {
  return http('/roles', { method: 'POST', data: payload })
}

/** 编辑角色 */
export function updateRole(id: number, payload: RolePayload) {
  return http(`/roles/${id}`, { method: 'PUT', data: payload })
}

/** 删除角色 */
export function deleteRole(id: number) {
  return http(`/roles/${id}`, { method: 'DELETE' })
}

/** 权限树 */
export function fetchPermissionTree() {
  return http<PermissionNode[]>('/permissions', { method: 'GET' })
}
