/**
 * 埋点上报接口（API-EVT-001）
 * 契约：POST /api/v1/events/report（需登录）
 *       Body { events: TrackEventItem[] }（1~50 条），响应 { accepted: number }
 * Mock 模式下仅 console.log，不发真实请求。
 */

import { http } from '@/utils/request'
import { USE_MOCK } from './config'
import type { TrackEventItem } from '@/types'

/** 批量上报埋点事件 */
export async function reportEvents(events: TrackEventItem[]): Promise<{ accepted: number }> {
  if (USE_MOCK) {
    console.log('[event] reportEvents (mock):', events)
    return { accepted: events.length }
  }
  return http.post<{ accepted: number }>('/api/v1/events/report', { events })
}
