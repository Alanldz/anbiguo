// 配置中心 API（API-ADM-CFG-001/002）
import { http } from './request'
import type { ConfigListData, ConfigUpdatePayload } from '@/types/api.d'

/** 配置列表（按分组） */
export function fetchConfigList(group?: string) {
  return http<ConfigListData>('/configs', { method: 'GET', params: group ? { group } : undefined })
}

/** 修改配置项 */
export function updateConfig(id: number, payload: ConfigUpdatePayload) {
  return http(`/configs/${id}`, { method: 'PUT', data: payload })
}
