/**
 * 考试相关接口
 * 接口编号见 docs/04-API接口规范与登记表.md §三
 */

import { http, requestPage } from '@/utils/request'
import { USE_MOCK, mockDelay } from './config'
import { mockQuestions } from '@/mock'
import type { PageData, PageParams, Question } from '@/types'

/** 试卷 */
export interface ExamPaper {
  id: number
  title: string
  bank_id: number
  duration_minutes: number
  total_score: number
  question_count: number
  questions?: Question[]
}

/** 考试记录 */
export interface ExamRecord {
  id: number
  paper_id: number
  title: string
  score: number
  total_score: number
  correct_count: number
  question_count: number
  cost_seconds: number
  created_at: string
}

/** API-EXM-001 发起 / 生成试卷 */
export function createExamPaper(data: {
  bank_id: number
  question_count: number
  duration_minutes: number
  types?: number[]
}): Promise<ExamPaper> {
  if (USE_MOCK) {
    return mockDelay({
      id: 9001,
      title: '模拟考试',
      bank_id: data.bank_id,
      duration_minutes: data.duration_minutes,
      total_score: 100,
      question_count: mockQuestions.length,
      questions: mockQuestions
    })
  }
  return http.post<ExamPaper>('/api/v1/exam-papers', data)
}

/** API-EXM-002 试卷详情（含题目） */
export function fetchExamPaper(id: number): Promise<ExamPaper> {
  if (USE_MOCK) {
    return mockDelay({
      id,
      title: '模拟考试',
      bank_id: 1024,
      duration_minutes: 60,
      total_score: 100,
      question_count: mockQuestions.length,
      questions: mockQuestions
    })
  }
  return http.get<ExamPaper>(`/api/v1/exam-papers/${id}`)
}

/** API-EXM-003 交卷（后端需做幂等） */
export function submitExam(data: {
  paper_id: number
  answers: Array<{ question_id: number; answer: string }>
  cost_seconds: number
}): Promise<ExamRecord> {
  if (USE_MOCK) {
    return mockDelay({
      id: 3001,
      paper_id: data.paper_id,
      title: '模拟考试',
      score: 82,
      total_score: 100,
      correct_count: 9,
      question_count: 11,
      cost_seconds: data.cost_seconds,
      created_at: '2026-09-15 11:30:00'
    })
  }
  return http.post<ExamRecord>('/api/v1/exam-records', data)
}

/** API-EXM-004 成绩与试卷回顾 */
export function fetchExamRecord(id: number): Promise<ExamRecord> {
  if (USE_MOCK) {
    return mockDelay({
      id,
      paper_id: 9001,
      title: '模拟考试',
      score: 82,
      total_score: 100,
      correct_count: 9,
      question_count: 11,
      cost_seconds: 1830,
      created_at: '2026-09-15 11:30:00'
    })
  }
  return http.get<ExamRecord>(`/api/v1/exam-records/${id}`)
}

/** API-EXM-005 考试记录列表 */
export function fetchExamRecords(params: PageParams): Promise<PageData<ExamRecord>> {
  if (USE_MOCK) {
    return mockDelay({
      list: [
        {
          id: 3001,
          paper_id: 9001,
          title: '英语-260117 模拟考试',
          score: 82,
          total_score: 100,
          correct_count: 9,
          question_count: 11,
          cost_seconds: 1830,
          created_at: '2026-09-15 11:30:00'
        },
        {
          id: 3002,
          paper_id: 9002,
          title: '毛概2412 章节测验',
          score: 91,
          total_score: 100,
          correct_count: 20,
          question_count: 22,
          cost_seconds: 2400,
          created_at: '2026-09-12 20:11:00'
        }
      ],
      pagination: { page: 1, page_size: 20, total: 2, total_pages: 1 }
    })
  }
  return requestPage<ExamRecord>('/api/v1/exam-records', params)
}
