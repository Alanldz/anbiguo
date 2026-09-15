/**
 * 学习资料相关接口
 * 接口编号见 client/API-CONTRACT.md（API-FIL-003，数据源 file_assets biz_type=3）
 */

import { requestPage } from '@/utils/request'
import { USE_MOCK, mockDelay } from './config'
import { mockResources } from '@/mock'
import type { PageData, PageParams, ResourceItem } from '@/types'

/** API-FIL-003 学习资料列表（bank_id 可选限定题库） */
export function fetchResources(
  params: PageParams & { bank_id?: number }
): Promise<PageData<ResourceItem>> {
  if (USE_MOCK) {
    const { page = 1, page_size = 20, bank_id } = params
    const filtered = bank_id ? mockResources.filter((item) => item.bank_id === bank_id) : mockResources
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
  return requestPage<ResourceItem>('/api/v1/file-assets', { ...params, biz_type: 3 })
}
