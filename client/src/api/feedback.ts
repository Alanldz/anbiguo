/**
 * 意见反馈接口
 * 接口编号：API-FBK-001 提交意见反馈（POST /api/v1/feedbacks）
 * 登记见 docs/04-API接口规范与登记表.md §二、client/API-CONTRACT.md §反馈接口
 */

import { http } from '@/utils/request'
import { USE_MOCK, mockDelay } from './config'
import type { FeedbackSubmitParams } from '@/types'

/** API-FBK-001 提交意见反馈 */
export function submitFeedback(data: FeedbackSubmitParams): Promise<{ submitted: true; message: string }> {
  if (USE_MOCK) return mockDelay({ submitted: true, message: '感谢反馈，我们会尽快处理' })
  return http.post<{ submitted: true; message: string }>('/api/v1/feedbacks', data)
}
