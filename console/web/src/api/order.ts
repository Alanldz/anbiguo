// 订单与会员 API（API-CSL-ORD-001 ~ 004）
import { http } from './request'
import type {
  OrderItem,
  MemberInfo,
  MemberPlan,
  PaginatedData,
  OrderListQuery,
} from '@/types/api.d'

/** API-CSL-ORD-001 我的订单 */
export function fetchOrders(params: OrderListQuery) {
  return http<PaginatedData<OrderItem>>('/orders', { method: 'GET', params })
}

/** API-CSL-ORD-002 订单详情 */
export function fetchOrderDetail(id: number) {
  return http<OrderItem>(`/orders/${id}`, { method: 'GET' })
}

/** API-CSL-ORD-003 我的会员 */
export function fetchMember() {
  return http<MemberInfo>('/member', { method: 'GET' })
}

/** API-CSL-ORD-004 会员套餐 */
export function fetchMemberPlans() {
  return http<MemberPlan[]>('/member-plans', { method: 'GET' })
}
