// 题目管理 API（API-CSL-QST-001 ~ 007）
import { http } from './request'
import type {
  QuestionItem,
  QuestionDetail,
  QuestionCreatePayload,
  QuestionUpdatePayload,
  QuestionBatchDeletePayload,
  QuestionBatchMovePayload,
  PaginatedData,
  QuestionListQuery,
} from '@/types/api.d'

/** API-CSL-QST-001 题目列表 */
export function fetchQuestions(bankId: number, params: QuestionListQuery) {
  return http<PaginatedData<QuestionItem>>(`/question-banks/${bankId}/questions`, {
    method: 'GET',
    params,
  })
}

/** API-CSL-QST-002 题目详情（含选项） */
export function fetchQuestionDetail(id: number) {
  return http<QuestionDetail>(`/questions/${id}`, { method: 'GET' })
}

/** API-CSL-QST-003 新增题目 */
export function createQuestion(bankId: number, payload: QuestionCreatePayload) {
  return http<{ id: number }>(`/question-banks/${bankId}/questions`, { method: 'POST', data: payload })
}

/** API-CSL-QST-004 更新题目 */
export function updateQuestion(id: number, payload: QuestionUpdatePayload) {
  return http(`/questions/${id}`, { method: 'PUT', data: payload })
}

/** API-CSL-QST-005 删除题目 */
export function deleteQuestion(id: number) {
  return http(`/questions/${id}`, { method: 'DELETE' })
}

/** API-CSL-QST-006 批量删除题目 */
export function batchDeleteQuestions(payload: QuestionBatchDeletePayload) {
  return http('/questions/batch-delete', { method: 'POST', data: payload })
}

/** API-CSL-QST-007 批量移动章节 */
export function batchMoveQuestions(payload: QuestionBatchMovePayload) {
  return http('/questions/batch-move', { method: 'POST', data: payload })
}
