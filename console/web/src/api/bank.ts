// 我的题库 API（API-CSL-BANK-001 ~ 007）
import { http } from './request'
import type {
  BankItem,
  BankCreatePayload,
  BankUpdatePayload,
  BankExportData,
  CategoryNode,
  PaginatedData,
  BankListQuery,
} from '@/types/api.d'

/** API-CSL-BANK-001 题库列表 */
export function fetchBankList(params: BankListQuery) {
  return http<PaginatedData<BankItem>>('/question-banks', { method: 'GET', params })
}

/** API-CSL-BANK-002 题库详情 */
export function fetchBankDetail(id: number) {
  return http<BankItem>(`/question-banks/${id}`, { method: 'GET' })
}

/** API-CSL-BANK-003 新建题库 */
export function createBank(payload: BankCreatePayload) {
  return http<{ id: number }>('/question-banks', { method: 'POST', data: payload })
}

/** API-CSL-BANK-004 更新/重命名题库 */
export function updateBank(id: number, payload: BankUpdatePayload) {
  return http(`/question-banks/${id}`, { method: 'PUT', data: payload })
}

/** API-CSL-BANK-005 删除题库 */
export function deleteBank(id: number) {
  return http(`/question-banks/${id}`, { method: 'DELETE' })
}

/** API-CSL-BANK-006 导出题库 */
export function exportBank(id: number) {
  return http<BankExportData>(`/question-banks/${id}/export`, { method: 'GET' })
}

/** API-CSL-BANK-007 分类树（下拉用） */
export function fetchBankCategories() {
  return http<CategoryNode[]>('/bank-categories', { method: 'GET' })
}
