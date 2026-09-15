// 配置中心 API（API-ADM-CFG-001/002/ADM-101）
import { http } from './request'
import type { ConfigListData, ConfigUpdatePayload, ConfigTestResult } from '@/types/api.d'

/** 配置列表（按分组） */
export function fetchConfigList(group?: string) {
  return http<ConfigListData>('/configs', { method: 'GET', params: group ? { group } : undefined })
}

/** 修改配置项 */
export function updateConfig(id: number, payload: ConfigUpdatePayload) {
  return http(`/configs/${id}`, { method: 'PUT', data: payload })
}

/** API-ADM-101 配置连通性测试 */
export function testConfig(id: number) {
  return http<ConfigTestResult>(`/configs/${id}/test`, { method: 'POST' })
}
