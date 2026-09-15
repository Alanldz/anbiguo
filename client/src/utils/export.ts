/**
 * 题库导出工具（客户端聚合导出）
 * 说明：调题库详情 + 题目列表接口，分页循环取全部题目，
 *       组装 {bank, questions, exported_at} 后在 H5 端以 JSON 文件下载；
 *       非 H5 端提示到 H5/网页端操作。Mock 模式下读的是题库 api 的 Mock 分支，同样可用。
 */

import { fetchBankDetail } from '@/api/bank'
import { fetchQuestions } from '@/api/question'
import type { Question, QuestionBank } from '@/types'

/** 导出文件结构（题库名.json 的内容） */
export interface BankExportPayload {
  bank: QuestionBank
  questions: Question[]
  exported_at: string
}

/** 分页取题每页条数（一次性尽量取大页，减少请求轮数） */
const EXPORT_PAGE_SIZE = 100

/** 本地时间格式化为 Y-m-d H:i:s */
function formatLocalTime(date: Date): string {
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`
}

/** 拉取题库详情与全部题目并组装导出结构 */
export async function collectBankExport(bankId: number): Promise<BankExportPayload> {
  const bank = await fetchBankDetail(bankId)
  const questions: Question[] = []
  let page = 1
  let totalPages = 1
  // 分页循环取全部题目（以接口返回的 total_pages 为准，防御性上限 1000 页）
  while (page <= totalPages && page <= 1000) {
    const result = await fetchQuestions(bankId, { page, page_size: EXPORT_PAGE_SIZE })
    questions.push(...result.list)
    totalPages = result.pagination.total_pages
    page += 1
  }
  return { bank, questions, exported_at: formatLocalTime(new Date()) }
}

/**
 * 导出题库为 JSON 文件：
 *  - H5：Blob + <a download> 下载为「题库名.json」，返回 true；
 *  - 非 H5：toast 提示到网页端操作，返回 false。
 */
export async function exportBankAsJson(bankId: number): Promise<boolean> {
  const payload = await collectBankExport(bankId)

  // #ifdef H5
  const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const anchor = document.createElement('a')
  anchor.href = url
  anchor.download = `${payload.bank.title}.json`
  anchor.click()
  URL.revokeObjectURL(url)
  return true
  // #endif

  // #ifndef H5
  uni.showToast({ title: '请在 H5/网页端使用导出功能', icon: 'none' })
  return false
  // #endif
}
