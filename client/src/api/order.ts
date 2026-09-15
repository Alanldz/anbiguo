/**
 * 订单相关接口
 * 接口编号见 client/API-CONTRACT.md §八（仅本人订单）
 */

import { requestPage } from '@/utils/request'
import { USE_MOCK, mockDelay } from './config'
import { mockOrders } from '@/mock'
import type { OrderItem, PageData, PageParams } from '@/types'

/** API-ORD-001 我的订单列表（status 可选：0 待支付 1 已支付 2 已取消 3 已退款 4 已关闭） */
export function fetchOrders(
  params: PageParams & { status?: number }
): Promise<PageData<OrderItem>> {
  if (USE_MOCK) {
    const { page = 1, page_size = 20, status } = params
    const filtered = typeof status === 'number' ? mockOrders.filter((item) => item.status === status) : mockOrders
    return mockDelay({
      list: filtered.slice((page - 1) * page_size, page * page_size),
      pagination: {
        page,
        page_size,
        total: filtered.length,
        total_pages: Math.ceil(filtered.length / page_size)
      }
    })
  }
  return requestPage<OrderItem>('/api/v1/orders', params)
}
