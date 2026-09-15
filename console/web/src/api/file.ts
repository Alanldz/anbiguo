// 学习资料 API（API-CSL-FIL-001 ~ 009）
import { http } from './request'
import type {
  FileCategoryItem,
  FileCategoryPayload,
  FileAssetItem,
  FileAssetRegisterPayload,
  FileUploadToken,
  FileUploadTokenPayload,
  FileDownloadUrl,
  PaginatedData,
  FileAssetListQuery,
} from '@/types/api.d'

/** API-CSL-FIL-001 分类列表 */
export function fetchFileCategories() {
  return http<FileCategoryItem[]>('/file-categories', { method: 'GET' })
}

/** API-CSL-FIL-002 新建分类 */
export function createFileCategory(payload: FileCategoryPayload) {
  return http<{ id: number }>('/file-categories', { method: 'POST', data: payload })
}

/** API-CSL-FIL-003 更新分类 */
export function updateFileCategory(id: number, payload: FileCategoryPayload) {
  return http(`/file-categories/${id}`, { method: 'PUT', data: payload })
}

/** API-CSL-FIL-004 删除分类 */
export function deleteFileCategory(id: number) {
  return http(`/file-categories/${id}`, { method: 'DELETE' })
}

/** API-CSL-FIL-005 资料列表 */
export function fetchFileAssets(params: FileAssetListQuery) {
  return http<PaginatedData<FileAssetItem>>('/file-assets', { method: 'GET', params })
}

/** API-CSL-FIL-006 上传后登记 */
export function registerFileAsset(payload: FileAssetRegisterPayload) {
  return http<{ id: number }>('/file-assets', { method: 'POST', data: payload })
}

/** API-CSL-FIL-007 删除资料 */
export function deleteFileAsset(id: number) {
  return http(`/file-assets/${id}`, { method: 'DELETE' })
}

/** API-CSL-FIL-008 获取下载地址 */
export function fetchFileDownloadUrl(id: number) {
  return http<FileDownloadUrl>(`/file-assets/${id}/url`, { method: 'GET' })
}

/** API-CSL-FIL-009 七牛直传凭证 */
export function fetchUploadToken(payload: FileUploadTokenPayload) {
  return http<FileUploadToken>('/files/upload-token', { method: 'POST', data: payload })
}
