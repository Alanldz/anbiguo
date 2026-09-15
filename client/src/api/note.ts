/**
 * 笔记相关接口
 * 接口编号见 docs/04-API接口规范与登记表.md §三
 */

import { http, requestPage } from '@/utils/request'
import { USE_MOCK, mockDelay } from './config'
import { mockNotes } from '@/mock'
import type { NoteItem, PageData, PageParams } from '@/types'

/** API-NOTE-001 我的笔记列表 */
export function fetchNotes(
  params: PageParams & { bank_id?: number; keyword?: string }
): Promise<PageData<NoteItem>> {
  if (USE_MOCK) {
    const { page = 1, page_size = 20, bank_id, keyword } = params
    let filtered = mockNotes
    if (bank_id) filtered = filtered.filter((item) => item.bank_id === bank_id)
    if (keyword) {
      const kw = keyword.trim()
      filtered = filtered.filter(
        (item) => item.question_title.includes(kw) || item.content.includes(kw) || item.bank_name.includes(kw)
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
  return requestPage<NoteItem>('/api/v1/notes', params)
}
