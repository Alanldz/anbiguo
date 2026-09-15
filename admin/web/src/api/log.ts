// 日志 API（API-ADM-SYS-002）
import { http } from './request'
import type { PaginatedData, OperationLogItem, LoginLogItem, ListQuery } from '@/types/api.d'

/** 操作日志列表 */
export function fetchOperationLogs(params: ListQuery & { admin_id?: number; action?: string }) {
  return http<PaginatedData<OperationLogItem>>('/logs/operation', { method: 'GET', params })
}

/** 登录日志列表 */
export function fetchLoginLogs(params: ListQuery) {
  return http<PaginatedData<LoginLogItem>>('/logs/login', { method: 'GET', params })
}
