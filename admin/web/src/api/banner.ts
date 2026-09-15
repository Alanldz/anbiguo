// 轮播管理 API（API-ADM-OPR-001）
import { http } from './request'
import type { BannerItem, BannerPayload, PaginatedData, ListQuery } from '@/types/api.d'

/** 轮播列表 */
export function fetchBannerList(params: ListQuery) {
  return http<PaginatedData<BannerItem>>('/banners', { method: 'GET', params })
}

/** 新建轮播 */
export function createBanner(payload: BannerPayload) {
  return http('/banners', { method: 'POST', data: payload })
}

/** 编辑轮播 */
export function updateBanner(id: number, payload: BannerPayload) {
  return http(`/banners/${id}`, { method: 'PUT', data: payload })
}

/** 删除轮播 */
export function deleteBanner(id: number) {
  return http(`/banners/${id}`, { method: 'DELETE' })
}
