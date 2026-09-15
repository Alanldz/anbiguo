// 考试记录 API（API-CSL-EXM-001 ~ 002）
import { http } from './request'
import type {
  ExamRecordItem,
  ExamRecordDetail,
  PaginatedData,
  ExamRecordListQuery,
} from '@/types/api.d'

/** API-CSL-EXM-001 考试记录列表 */
export function fetchExamRecords(params: ExamRecordListQuery) {
  return http<PaginatedData<ExamRecordItem>>('/exam-records', { method: 'GET', params })
}

/** API-CSL-EXM-002 考试记录详情（含作答明细） */
export function fetchExamRecordDetail(id: number) {
  return http<ExamRecordDetail>(`/exam-records/${id}`, { method: 'GET' })
}
