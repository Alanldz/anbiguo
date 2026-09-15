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

/** 导入模板结构（API-IMP-003 GET /import/template 响应） */
export interface ImportTemplate {
  columns: Array<{ name: string; required: boolean; desc: string }>
  sample_url: string
}

/**
 * API-IMP-003 获取导入模板（固定模板结构 + 示例下载地址）
 * Mock 返回契约 §六 的固定结构；真实模式由调用方处理 sample_url（复制链接 / 下载）。
 */
export async function fetchImportTemplate(): Promise<ImportTemplate> {
  if (USE_MOCK) {
    return mockDelay({
      columns: [
        { name: '题干', required: true, desc: '题目内容，支持富文本' },
        { name: '题型', required: true, desc: '单选/多选/判断/填空/简答' },
        { name: '选项', required: false, desc: '选择题填写，格式：A.选项内容|B.选项内容' },
        { name: '答案', required: true, desc: '选择题填选项字母，判断题填 对/错' },
        { name: '解析', required: false, desc: '答案解析' },
        { name: '难度', required: false, desc: '易/中/难' },
        { name: '章节', required: false, desc: '所属章节名称' }
      ],
      sample_url: ''
    })
  }
  return http.get<ImportTemplate>('/api/v1/import/template')
}

/** API-IMP-004 手动录入题目（本期占位：创建待校对导入任务，返回任务 id） */
export function createQuestionManual(data: {
  bank_id: number
  type: number
  title: string
  options: Array<{ key: string; content: string }>
  answer: string
  analysis?: string
  difficulty?: number
  score?: number
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
