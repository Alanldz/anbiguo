// 学习概览 API（API-CSL-STAT-001）
import { http } from './request'
import type { StatisticsOverview } from '@/types/api.d'

/** API-CSL-STAT-001 学习概览 */
export function fetchOverview() {
  return http<StatisticsOverview>('/statistics/overview', { method: 'GET' })
}
