// 题库导入 API（API-CSL-IMP-001 ~ 005）
import { http } from './request'
import type {
  ImportTaskItem,
  ImportTaskDetail,
  ImportTaskCreatePayload,
  ImportTemplate,
  ImportTaskCreateResult,
  PaginatedData,
  ImportTaskListQuery,
} from '@/types/api.d'

/** API-CSL-IMP-001 创建导入任务 */
export function createImportTask(payload: ImportTaskCreatePayload) {
  return http<ImportTaskCreateResult>('/import-tasks', { method: 'POST', data: payload })
}

/** API-CSL-IMP-002 导入任务列表 */
export function fetchImportTasks(params: ImportTaskListQuery) {
  return http<PaginatedData<ImportTaskItem>>('/import-tasks', { method: 'GET', params })
}

/** API-CSL-IMP-003 导入任务详情（含解析结果） */
export function fetchImportTaskDetail(id: number) {
  return http<ImportTaskDetail>(`/import-tasks/${id}`, { method: 'GET' })
}

/** API-CSL-IMP-004 删除任务 */
export function deleteImportTask(id: number) {
  return http(`/import-tasks/${id}`, { method: 'DELETE' })
}

/** API-CSL-IMP-005 导入模板说明 */
export function fetchImportTemplate() {
  return http<ImportTemplate>('/import-tasks/template', { method: 'GET' })
}
