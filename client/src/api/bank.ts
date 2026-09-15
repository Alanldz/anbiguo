/**
 * 题库相关接口
 * 接口编号见 docs/04-API接口规范与登记表.md §三
 */

import { http, requestPage } from '@/utils/request'
import { USE_MOCK, mockDelay } from './config'
import { mockBanks, mockCategories, mockRecycleBanks } from '@/mock'
import type { BankCategory, PageData, PageParams, QuestionBank, RecycleBankItem } from '@/types'

/** API-BANK-001 题库分类列表 */
export function fetchCategories(): Promise<BankCategory[]> {
  if (USE_MOCK) return mockDelay(mockCategories)
  return http.get<BankCategory[]>('/api/v1/bank-categories')
}

/** API-BANK-002 我的题库列表 */
export function fetchMyBanks(params: PageParams & { keyword?: string }): Promise<PageData<QuestionBank>> {
  if (USE_MOCK) {
    const { page = 1, page_size = 20, keyword } = params
    const filtered = keyword ? mockBanks.filter((b) => b.title.includes(keyword)) : mockBanks
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
  return requestPage<QuestionBank>('/api/v1/question-banks', params)
}

/** API-BANK-003 题库详情 */
export function fetchBankDetail(id: number): Promise<QuestionBank> {
  if (USE_MOCK) {
    const bank = mockBanks.find((item) => item.id === id) ?? mockBanks[0]
    return mockDelay(bank)
  }
  return http.get<QuestionBank>(`/api/v1/question-banks/${id}`)
}

/** API-BANK-004 创建题库 */
export function createBank(data: { title: string; category_id?: number }): Promise<{ id: number }> {
  return http.post<{ id: number }>('/api/v1/question-banks', data)
}

/** API-BANK-005 更新 / 重命名题库 */
export function updateBank(id: number, data: { title?: string; category_id?: number }): Promise<void> {
  return http.put<void>(`/api/v1/question-banks/${id}`, data)
}

/** API-BANK-006 删除题库 */
export function deleteBank(id: number): Promise<void> {
  return http.del<void>(`/api/v1/question-banks/${id}`)
}

/** API-BANK-007 题库市场列表 */
export function fetchMarketBanks(
  params: PageParams & { category_id?: number }
): Promise<PageData<QuestionBank>> {
  if (USE_MOCK) {
    return mockDelay({
      list: mockBanks.filter((item) => item.source_type === 2),
      pagination: { page: 1, page_size: 20, total: 1, total_pages: 1 }
    })
  }
  return requestPage<QuestionBank>('/api/v1/bank-market', params)
}

/** API-BANK-008 回收站列表 */
export function fetchRecycleBanks(
  params: PageParams & { keyword?: string }
): Promise<PageData<RecycleBankItem>> {
  if (USE_MOCK) {
    const { page = 1, page_size = 20, keyword } = params
    const filtered = keyword
      ? mockRecycleBanks.filter((item) => item.title.includes(keyword.trim()))
      : mockRecycleBanks
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
  return requestPage<RecycleBankItem>('/api/v1/question-banks/recycle', params)
}

/** API-BANK-009 恢复题库 */
export function restoreBank(id: number): Promise<{ restored: true } & QuestionBank> {
  if (USE_MOCK) {
    const bank = mockRecycleBanks.find((item) => item.id === id) ?? mockRecycleBanks[0]
    return mockDelay({ restored: true, ...bank })
  }
  return http.put<{ restored: true } & QuestionBank>(`/api/v1/question-banks/${id}/restore`)
}
