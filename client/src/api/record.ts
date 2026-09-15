/**
 * 练习记录相关接口
 * 接口编号见 docs/04-API接口规范与登记表.md §三
 */

import { http, requestPage } from '@/utils/request'
import { USE_MOCK, mockDelay } from './config'
import { mockPracticeRecords } from '@/mock'
import type { PageData, PageParams, PracticeRecordItem, PracticeStatus } from '@/types'

/** API-REC-001 练习记录列表 */
export function fetchPracticeRecords(
  params: PageParams & { bank_id?: number; status?: PracticeStatus }
): Promise<PageData<PracticeRecordItem>> {
  if (USE_MOCK) {
    const { page = 1, page_size = 20, bank_id, status } = params
    let filtered = mockPracticeRecords
    if (bank_id) filtered = filtered.filter((item) => item.bank_id === bank_id)
    if (status !== undefined) filtered = filtered.filter((item) => item.status === status)
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
  return requestPage<PracticeRecordItem>('/api/v1/practice-records', params)
}
