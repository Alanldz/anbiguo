// 分类管理 API（API-ADM-100）
import { http } from './request'
import type { CategoryItem, CategoryPayload, CategoryUpdatePayload } from '@/types/api.d'

/** API-ADM-100 分类列表（type 区分 bank/files，后端 {list:[...], total} 包裹，按 parent_id 平铺返回） */
export function fetchCategories(type: 'bank' | 'file') {
  return http<{ list: CategoryItem[]; total: number }>('/categories', { method: 'GET', params: { type } })
}

/** API-ADM-100 新建分类 */
export function createCategory(payload: CategoryPayload) {
  return http('/categories', { method: 'POST', data: payload })
}

/** API-ADM-100 编辑分类（code 不可编辑） */
export function updateCategory(id: number, payload: CategoryUpdatePayload) {
  return http(`/categories/${id}`, { method: 'PUT', data: payload })
}

/** API-ADM-100 删除分类（有子级/在用会报错，错误提示由拦截器展示 message） */
export function deleteCategory(id: number) {
  return http(`/categories/${id}`, { method: 'DELETE' })
}
