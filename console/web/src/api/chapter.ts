// 章节 API（API-CSL-CHP-001 ~ 004）
import { http } from './request'
import type { ChapterItem, ChapterCreatePayload, ChapterUpdatePayload } from '@/types/api.d'

/** API-CSL-CHP-001 章节列表（扁平数组） */
export function fetchChapters(bankId: number) {
  return http<ChapterItem[]>(`/question-banks/${bankId}/chapters`, { method: 'GET' })
}

/** API-CSL-CHP-002 新建章节 */
export function createChapter(bankId: number, payload: ChapterCreatePayload) {
  return http<{ id: number }>(`/question-banks/${bankId}/chapters`, { method: 'POST', data: payload })
}

/** API-CSL-CHP-003 更新章节 */
export function updateChapter(id: number, payload: ChapterUpdatePayload) {
  return http(`/chapters/${id}`, { method: 'PUT', data: payload })
}

/** API-CSL-CHP-004 删除章节 */
export function deleteChapter(id: number) {
  return http(`/chapters/${id}`, { method: 'DELETE' })
}
