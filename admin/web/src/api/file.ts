// 文件资源管理 API（API-ADM-103）
import { http } from './request'
import type { FileListData, FileQuery } from '@/types/api.d'

/** API-ADM-103 文件资源列表 */
export function fetchFiles(params: FileQuery) {
  return http<FileListData>('/files', { method: 'GET', params })
}

/** API-ADM-103 删除文件（二次确认后软删） */
export function deleteFile(id: number) {
  return http(`/files/${id}`, { method: 'DELETE' })
}
