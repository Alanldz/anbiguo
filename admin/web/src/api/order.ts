// 订单管理 / 退款 API（API-ADM-102）
import { http } from './request'
import type { OrderListData, OrderQuery, OrderRefundPayload } from '@/types/api.d'

/** API-ADM-102 订单列表 */
export function fetchOrders(params: OrderQuery) {
  return http<OrderListData>('/orders', { method: 'GET', params })
}

/** API-ADM-102 订单退款（仅已支付订单可调） */
export function refundOrder(id: number, payload: OrderRefundPayload) {
  return http(`/orders/${id}/refund`, { method: 'POST', data: payload })
}
