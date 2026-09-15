/**
 * 题目 / 错题 / 收藏 / 笔记相关接口
 * 接口编号见 docs/04-API接口规范与登记表.md §三
 */

import { http, requestPage } from '@/utils/request'
import { USE_MOCK, mockDelay } from './config'
import { mockErrorProne, mockQuestions } from '@/mock'
import type { ErrorProneItem, PageData, PageParams, Question, QuestionType } from '@/types'

/** API-QUE-001 题目列表（练习取题） */
export function fetchQuestions(
  bankId: number,
  params: PageParams & { mode?: 'sequence' | 'random' | 'chapter'; chapter_id?: number }
): Promise<PageData<Question>> {
  if (USE_MOCK) {
    const { page = 1, page_size = 20 } = params
    return mockDelay({
      list: mockQuestions,
      pagination: {
        page,
        page_size,
        total: mockQuestions.length,
        total_pages: 1
      }
    })
  }
  return requestPage<Question>(`/api/v1/question-banks/${bankId}/questions`, params)
}

/** API-QUE-002 提交单题作答 */
export function submitAnswer(data: {
  question_id: number
  answer: string
  cost_seconds?: number
}): Promise<{ correct: boolean; answer: string; analysis: string }> {
  if (USE_MOCK) {
    const question = mockQuestions.find((item) => item.id === data.question_id) ?? mockQuestions[0]
    return mockDelay({ correct: data.answer === question.answer, answer: question.answer, analysis: question.analysis })
  }
  return http.post(`/api/v1/questions/${data.question_id}/answer`, data)
}

/** API-QUE-003 收藏 / 取消收藏 */
export function toggleFavorite(questionId: number, favorite: boolean): Promise<void> {
  if (USE_MOCK) return mockDelay(undefined as void)
  return http.post<void>(`/api/v1/questions/${questionId}/favorite`, { favorite })
}

/** API-QUE-004 写 / 改笔记 */
export function saveNote(questionId: number, content: string): Promise<void> {
  if (USE_MOCK) return mockDelay(undefined as void)
  return http.put<void>(`/api/v1/questions/${questionId}/note`, { content })
}

/** API-QUE-005 试题报错 */
export function reportQuestion(questionId: number, reason: string, images?: string[]): Promise<void> {
  return http.post<void>(`/api/v1/questions/${questionId}/report`, { reason, images })
}

/** API-WRG-001 错题列表 */
export function fetchWrongQuestions(
  params: PageParams & { bank_id?: number; question_type?: QuestionType }
): Promise<PageData<Question>> {
  if (USE_MOCK) {
    return mockDelay({
      list: mockQuestions.slice(0, 2),
      pagination: { page: 1, page_size: 20, total: 2, total_pages: 1 }
    })
  }
  return requestPage<Question>('/api/v1/wrong-questions', params)
}

/** API-WRG-002 移除错题 */
export function removeWrongQuestion(id: number): Promise<void> {
  return http.del<void>(`/api/v1/wrong-questions/${id}`)
}

/** API-ERR-001 易错题集（bank_id 必填） */
export function fetchErrorProne(bankId: number, params: PageParams = {}): Promise<PageData<ErrorProneItem>> {
  if (USE_MOCK) {
    const { page = 1, page_size = 20 } = params
    const filtered = mockErrorProne.filter((item) => item.bank_id === bankId)
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
  return requestPage<ErrorProneItem>('/api/v1/error-prone-questions', {
    ...params,
    bank_id: bankId
  })
}
