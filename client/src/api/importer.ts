/**
 * 题库导入（AI 导题）相关接口
 * 接口编号见 docs/04-API接口规范与登记表.md §三
 */

import { http } from '@/utils/request'
import { USE_MOCK, mockDelay } from './config'
import { mockImportTasks } from '@/mock'
import type { ImportTask } from '@/types'

/** 上传凭证（七牛直传），见 docs/05-OSS存储与文件分类规范.md §四 */
export interface UploadToken {
  upload_token: string
  object_key: string
  domain: string
  expire_at: number
}

/** API-FIL-001 获取直传凭证 */
export function fetchUploadToken(data: {
  biz_type: number
  file_ext: string
  file_size: number
  bank_id?: number
  origin_name?: string
}): Promise<UploadToken> {
  return http.post<UploadToken>('/api/v1/files/upload-token', data)
}

/** API-FIL-002 上传完成回调登记 */
export function completeUpload(data: {
  object_key: string
  biz_type: number
  file_size: number
  file_hash?: string
  bank_id?: number
  origin_name?: string
}): Promise<{ file_id: number }> {
  return http.post<{ file_id: number }>('/api/v1/files/complete', data)
}

/** API-IMP-001 上传文档导题 */
export function createImportByUpload(data: {
  file_id: number
  bank_id?: number
  title?: string
  split_answer?: boolean
}): Promise<{ task_id: number; bank_id: number }> {
  if (USE_MOCK) return mockDelay({ task_id: 502, bank_id: 1024 })
  return http.post('/api/v1/import/upload', data)
}

/** API-IMP-002 查询解析进度 */
export function fetchImportTask(id: number): Promise<ImportTask> {
  if (USE_MOCK) {
    return mockDelay(mockImportTasks.find((item) => item.id === id) ?? mockImportTasks[0])
  }
  return http.get<ImportTask>(`/api/v1/import/tasks/${id}`)
}

/** API-IMP-003 下载导入模板（返回文件地址，由后端重定向到七牛签名 URL） */
export function fetchImportTemplateUrl(format: 'xlsx' | 'docx' = 'xlsx'): string {
  return `/api/v1/import/template?format=${format}`
}

/** API-IMP-004 手动录入题目 */
export function createQuestionManual(data: {
  bank_id: number
  type: number
  title: string
  options: Array<{ key: string; content: string }>
  answer: string
  analysis?: string
}): Promise<{ id: number }> {
  if (USE_MOCK) return mockDelay({ id: Date.now() })
  return http.post<{ id: number }>('/api/v1/import/manual', data)
}

/** API-IMP-005 拍照录题（OCR） */
export function createQuestionByOcr(data: {
  file_id: number
  bank_id?: number
}): Promise<{ task_id: number; text: string }> {
  if (USE_MOCK) return mockDelay({ task_id: 503, text: '识别到的题干内容（Mock）' })
  return http.post('/api/v1/import/ocr', data)
}
