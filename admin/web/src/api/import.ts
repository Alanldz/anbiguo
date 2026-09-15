// 导入任务（AI 导题）API
import { http } from './request'
import type { ImportTaskItem, PaginatedData, ListQuery } from '@/types/api.d'

/** 导入任务列表 */
export function fetchImportTasks(params: ListQuery) {
  return http<PaginatedData<ImportTaskItem>>('/import-tasks', { method: 'GET', params })
}
