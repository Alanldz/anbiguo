/**
 * 文件 / 学习资料相关接口
 * 接口编号见 docs/04-API接口规范与登记表.md §三
 */

import { http, requestPage } from '@/utils/request'
import { USE_MOCK, mockDelay } from './config'
import type { PageData, PageParams } from '@/types'

/** 文件资源，对应后端 file_assets 表 */
export interface FileAsset {
  id: number
  biz_type: number
  bank_id: number | null
  category_id: number | null
  origin_name: string
  object_key: string
  file_ext: string
  file_size: number
  url?: string
  created_at: string
}

/** API-FIL-003 学习资料列表 */
export function fetchFileAssets(
  params: PageParams & { bank_id?: number; category_id?: number }
): Promise<PageData<FileAsset>> {
  if (USE_MOCK) {
    return mockDelay({
      list: [
        {
          id: 7001,
          biz_type: 3,
          bank_id: 1024,
          category_id: 1,
          origin_name: '英语语法讲义.pdf',
          object_key: 'resource/1024/lecture_note/202609/res_1024_20260915114002_c8a1f0.pdf',
          file_ext: 'pdf',
          file_size: 2048000,
          created_at: '2026-09-15 11:40:02'
        }
      ],
      pagination: { page: 1, page_size: 20, total: 1, total_pages: 1 }
    })
  }
  return requestPage<FileAsset>('/api/v1/file-assets', params)
}
