/**
 * 收藏相关接口 + 账号注销
 * 接口编号见 docs/04-API接口规范与登记表.md §三
 * 说明：cancelAccount 属于 API-USER-004，按本次任务要求与收藏列表同文件组织。
 */

import { http, requestPage } from '@/utils/request'
import { USE_MOCK, mockDelay } from './config'
import { mockFavorites } from '@/mock'
import type { FavoriteItem, PageData, PageParams } from '@/types'

/** API-FAV-001 我的收藏列表 */
export function fetchFavorites(
  params: PageParams & { bank_id?: number; keyword?: string }
): Promise<PageData<FavoriteItem>> {
  if (USE_MOCK) {
    const { page = 1, page_size = 20, bank_id, keyword } = params
    let filtered = mockFavorites
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
  return requestPage<FavoriteItem>('/api/v1/favorites', params)
}

/** API-USER-004 账号注销 */
export function cancelAccount(data: { confirm: true; reason?: string }): Promise<{ canceled: true; message: string }> {
  if (USE_MOCK) return mockDelay({ canceled: true, message: '账号已注销' })
  return http.post<{ canceled: true; message: string }>('/api/v1/user/cancel', data)
}
