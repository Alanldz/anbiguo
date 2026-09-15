// 我的错题 API（API-CSL-WRG-001 ~ 003）
import { http } from './request'
import type {
  WrongQuestionItem,
  WrongBatchRemovePayload,
  PaginatedData,
  WrongQuestionListQuery,
} from '@/types/api.d'

/** API-CSL-WRG-001 错题列表 */
export function fetchWrongQuestions(params: WrongQuestionListQuery) {
  return http<PaginatedData<WrongQuestionItem>>('/wrong-questions', { method: 'GET', params })
}

/** API-CSL-WRG-002 移除单条 */
export function removeWrongQuestion(id: number) {
  return http(`/wrong-questions/${id}`, { method: 'DELETE' })
}

/** API-CSL-WRG-003 批量移除 */
export function batchRemoveWrongQuestions(payload: WrongBatchRemovePayload) {
  return http('/wrong-questions/batch-remove', { method: 'POST', data: payload })
}
