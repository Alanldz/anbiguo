/**
 * 考试相关接口
 * 接口编号见 docs/04-API接口规范与登记表.md §三（API-EXM-001 ~ 005）
 */

import { http, requestPage } from '@/utils/request'
import { USE_MOCK, mockDelay } from './config'
import { createMockPaper, gradeMockExam, mockExamRecords, mockPaperStore } from '@/mock'
import type {
  ExamPaper,
  ExamRecord,
  ExamRecordDetail,
  PageData,
  PageParams,
  QuestionBank
} from '@/types'
// 类型统一定义在 src/types/index.ts，此处 re-export 保持既有导入路径可用
export type { ExamPaper, ExamRecord, ExamRecordDetail }

/** API-EXM-001 发起 / 生成试卷 */
export function createExamPaper(data: {
  bank_id: number
  question_count: number
  duration_minutes: number
  types?: number[]
}): Promise<ExamPaper> {
  if (USE_MOCK) {
    // Mock：以预置题池抽题组卷，题库名称由调用方透传更准确，这里按 id 兜底
    const bankTitles: Record<number, string> = {
      1024: '英语-260117',
      1025: '法理学 250111考试导入',
      1026: '集大-毛概-1-11-AM9',
      1027: '毛概2412',
      1028: '2411新教材赠送模拟试卷2',
      1029: 'JC10心理咨询专业伦理单科作业题'
    }
    const paper = createMockPaper({
      bank_id: data.bank_id,
      bank_title: bankTitles[data.bank_id] ?? '自定义题库',
      question_count: data.question_count,
      duration_minutes: data.duration_minutes
    })
    return mockDelay(paper)
  }
  return http.post<ExamPaper>('/api/v1/exam-papers', data)
}

/** API-EXM-002 试卷详情（含题目，按试卷内 sort_order） */
export function fetchExamPaper(id: number): Promise<ExamPaper> {
  if (USE_MOCK) {
    const paper = mockPaperStore.get(id) ?? mockPaperStore.get(9001)!
    return mockDelay(paper)
  }
  return http.get<ExamPaper>(`/api/v1/exam-papers/${id}`)
}

/** API-EXM-003 交卷（后端需做幂等：同用户同试卷已交卷则返回既有成绩） */
export function submitExam(data: {
  paper_id: number
  answers: Array<{ question_id: number; answer: string }>
  cost_seconds: number
}): Promise<ExamRecord> {
  if (USE_MOCK) {
    const paper = mockPaperStore.get(data.paper_id) ?? mockPaperStore.get(9001)!
    return mockDelay(gradeMockExam(paper, data.answers, data.cost_seconds))
  }
  return http.post<ExamRecord>('/api/v1/exam-records', data)
}

/** API-EXM-004 成绩与试卷回顾（含逐题作答明细） */
export function fetchExamRecord(id: number): Promise<ExamRecordDetail> {
  if (USE_MOCK) {
    const record = mockExamRecords.find((item) => item.id === id) ?? mockExamRecords[0]
    return mockDelay(record)
  }
  return http.get<ExamRecordDetail>(`/api/v1/exam-records/${id}`)
}

/** API-EXM-005 考试记录列表 */
export function fetchExamRecords(
  params: PageParams & { bank_id?: number; keyword?: string }
): Promise<PageData<ExamRecord>> {
  if (USE_MOCK) {
    const { page = 1, page_size = 20, keyword } = params
    const filtered = keyword ? mockExamRecords.filter((item) => item.title.includes(keyword)) : mockExamRecords
    // 列表返回 ExamRecord，多余字段（answers 等）不影响展示
    return mockDelay({
      list: filtered.slice((page - 1) * page_size, page * page_size) as ExamRecord[],
      pagination: {
        page,
        page_size,
        total: filtered.length,
        total_pages: Math.ceil(filtered.length / page_size)
      }
    })
  }
  return requestPage<ExamRecord>('/api/v1/exam-records', params)
}
