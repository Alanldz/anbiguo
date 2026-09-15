// 题库管理 API（API-ADM-BANK-001/002）
import { http } from './request'
import type { BankItem, BankAuditPayload, BankStatusPayload, PaginatedData, ListQuery } from '@/types/api.d'

/** 题库列表 */
export function fetchBankList(params: ListQuery) {
  return http<PaginatedData<BankItem>>('/banks', { method: 'GET', params })
}

/** 题库审核（通过/拒绝 + 备注） */
export function auditBank(id: number, payload: BankAuditPayload) {
  return http(`/banks/${id}/audit`, { method: 'PUT', data: payload })
}

/** 题库上下架 */
export function updateBankStatus(id: number, payload: BankStatusPayload) {
  return http(`/banks/${id}/status`, { method: 'PUT', data: payload })
}
