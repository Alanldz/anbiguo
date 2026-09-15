// 会员套餐配置 API（API-ADM-105）
import { http } from './request'
import type { MemberPlanItem, MemberPlanUpdatePayload } from '@/types/api.d'

/** API-ADM-105 会员套餐列表（全量，后端 {list:[...]} 包裹） */
export function fetchMemberPlans() {
  return http<{ list: MemberPlanItem[] }>('/member-plans', { method: 'GET' })
}

/** API-ADM-105 编辑会员套餐（任意子集字段） */
export function updateMemberPlan(id: number, payload: MemberPlanUpdatePayload) {
  return http(`/member-plans/${id}`, { method: 'PUT', data: payload })
}
