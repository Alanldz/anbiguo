// 意见反馈处理 API（API-ADM-104）
import { http } from './request'
import type { FeedbackListData, FeedbackQuery, FeedbackHandlePayload } from '@/types/api.d'

/** API-ADM-104 反馈列表 */
export function fetchFeedbacks(params: FeedbackQuery) {
  return http<FeedbackListData>('/feedbacks', { method: 'GET', params })
}

/** API-ADM-104 处理反馈（已处理/已忽略 + 回复） */
export function handleFeedback(id: number, payload: FeedbackHandlePayload) {
  return http(`/feedbacks/${id}/handle`, { method: 'PUT', data: payload })
}
