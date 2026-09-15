/**
 * 斩题相关接口
 * 接口：API-MST-001 斩题列表、API-MST-002 找回（恢复斩题）
 */

import { http, requestPage } from '@/utils/request'
import { USE_MOCK, mockDelay } from './config'
import { mockMastered } from '@/mock'
import type { MasteredItem, PageData, PageParams } from '@/types'

/** API-MST-001 我的斩题列表 */
export function fetchMastered(
  params: PageParams & { bank_id?: number; keyword?: string }
): Promise<PageData<MasteredItem>> {
  if (USE_MOCK) {
    const { page = 1, page_size = 20, bank_id, keyword } = params
    let filtered = mockMastered
    if (bank_id) filtered = filtered.filter((item) => item.bank_id === bank_id)
    if (keyword) {
      const kw = keyword.trim()
      filtered = filtered.filter(
        (item) => item.question_title.includes(kw) || item.bank_name.includes(kw)
      )
    }
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
  return requestPage<MasteredItem>('/api/v1/mastered-questions', params)
}

/** API-MST-002 找回斩掉的题 */
export function restoreMastered(id: number): Promise<{ restored: true }> {
  if (USE_MOCK) return mockDelay({ restored: true })
  return http.put<{ restored: true }>(`/api/v1/mastered-questions/${id}/restore`)
}
