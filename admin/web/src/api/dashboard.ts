// 仪表盘 API（API-ADM-SYS-003）
import { http } from './request'
import type { DashboardSummary } from '@/types/api.d'

/** 仪表盘统计 */
export function fetchDashboardSummary() {
  return http<DashboardSummary>('/dashboard/summary', { method: 'GET' })
}
