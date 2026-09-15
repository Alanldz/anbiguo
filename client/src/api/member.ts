/**
 * 会员相关接口
 * 接口编号见 client/API-CONTRACT.md §七（支付待微信支付接入）
 */

import { http } from '@/utils/request'
import { USE_MOCK, mockDelay } from './config'
import { mockMemberPlans } from '@/mock'
import type { MemberPlan } from '@/types'

/** API-MBR-001 会员套餐列表（status=1 上架、按 sort_order 升序） */
export function fetchMemberPlans(): Promise<{ list: MemberPlan[] }> {
  if (USE_MOCK) return mockDelay({ list: mockMemberPlans })
  return http.get<{ list: MemberPlan[] }>('/api/v1/member/plans')
}

/**
 * API-MBR-002 开通会员下单（创建待支付订单）
 * TODO: 支付功能待微信支付接入（API-PAY-001/002），当前仅返回待支付订单，
 *       支付拉起与状态回调留待后续迭代实现。
 */
export function createMemberOrder(planId: number): Promise<{
  order_no: string
  order_type: number
  biz_id: number
  biz_title: string
  origin_amount: number
  discount_amount: number
  pay_amount: number
  status: number
  status_text: string
  expired_at: string
}> {
  if (USE_MOCK) {
    return mockDelay({
      order_no: `OD${Date.now()}`,
      order_type: 1,
      biz_id: planId,
      biz_title: '会员套餐',
      origin_amount: 0,
      discount_amount: 0,
      pay_amount: 0,
      status: 0,
      status_text: '待支付',
      expired_at: ''
    })
  }
  return http.post('/api/v1/member/orders', { plan_id: planId })
}
