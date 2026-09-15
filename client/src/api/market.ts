/**
 * 题库市场相关接口
 * 接口编号见 client/API-CONTRACT.md / docs/04-API接口规范与登记表.md §三
 */

import { requestPage } from '@/utils/request'
import { USE_MOCK, mockDelay } from './config'
import { mockMarketBanks } from '@/mock'
import type { MarketBankItem, PageData, PageParams } from '@/types'

/** API-BANK-007 题库市场列表（keyword 题库名模糊、category_id 分类筛选） */
export function fetchMarketBanks(
  params: PageParams & { keyword?: string; category_id?: number }
): Promise<PageData<MarketBankItem>> {
  if (USE_MOCK) {
    const { page = 1, page_size = 20, keyword, category_id } = params
    const filtered = mockMarketBanks.filter((item) => {
      const hitKeyword = keyword ? item.title.includes(keyword.trim()) : true
      // category_id 为 0 表示「热门推荐」（Mock 下不过滤分类）
      const hitCategory = category_id ? category_id === 0 || item.category_id === category_id : true
      return hitKeyword && hitCategory
    })
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
  return requestPage<MarketBankItem>('/api/v1/bank-market', params)
}
