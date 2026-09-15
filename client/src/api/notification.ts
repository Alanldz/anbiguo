/**
 * 消息通知相关接口
 * 接口：API-MSG-001 通知列表、API-MSG-002 未读数、API-MSG-003 标记已读、API-MSG-004 全部已读
 */

import { http, requestPage } from '@/utils/request'
import { USE_MOCK, mockDelay } from './config'
import { mockNotifications } from '@/mock'
import type { NotificationItem, PageData, PageParams } from '@/types'

/** API-MSG-001 消息通知列表 */
export function fetchNotifications(
  params: PageParams & { type?: number; is_read?: 0 | 1 }
): Promise<PageData<NotificationItem>> {
  if (USE_MOCK) {
    const { page = 1, page_size = 20, type, is_read } = params
    let filtered = mockNotifications
    if (type !== undefined) filtered = filtered.filter((item) => item.type === type)
    if (is_read !== undefined) filtered = filtered.filter((item) => item.is_read === is_read)
    return mockDelay({
      list: filtered.slice((page - 1) * page_size, page * page_size),
      pagination: {
        page,
        page_size,
        total: filtered.length,
        total_pages: Math.ceil(filtered.length / page_size)
      }
    })
  }
  return requestPage<NotificationItem>('/api/v1/notifications', params)
}

/** API-MSG-002 未读消息数 */
export function fetchUnreadCount(): Promise<{ count: number }> {
  if (USE_MOCK) {
    const count = mockNotifications.filter((item) => item.is_read === 0).length
    return mockDelay({ count })
  }
  return http.get<{ count: number }>('/api/v1/notifications/unread-count')
}

/** API-MSG-003 标记单条已读 */
export function markRead(id: number): Promise<{ marked: true }> {
  if (USE_MOCK) {
    const item = mockNotifications.find((n) => n.id === id)
    if (item) {
      item.is_read = 1
      item.read_at = '2026-02-19 12:00'
    }
    return mockDelay({ marked: true })
  }
  return http.put<{ marked: true }>(`/api/v1/notifications/${id}/read`)
}

/** API-MSG-004 全部已读 */
export function markAllRead(): Promise<{ marked: number }> {
  if (USE_MOCK) {
    let count = 0
    mockNotifications.forEach((item) => {
      if (item.is_read === 0) {
        item.is_read = 1
        item.read_at = '2026-02-19 12:00'
        count += 1
      }
    })
    return mockDelay({ marked: count })
  }
  return http.put<{ marked: number }>('/api/v1/notifications/read-all')
}
