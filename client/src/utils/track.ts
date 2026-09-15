/**
 * 轻量埋点工具（配合 src/api/event.ts 使用）
 * 说明：
 *  - track(event, payload?) 入内存队列，自动附带 page（当前页面路由）与 occurred_at；
 *  - flush() 批量上报：队列 ≥10 条即时触发，或 30 秒定时触发；
 *  - App onHide 时也会 flush（在 App.vue 中接入），上报失败仅 console.warn 不影响业务。
 */

import { reportEvents } from '@/api/event'
import type { TrackEventItem } from '@/types'

/** 触发批量上报的队列长度阈值 */
const FLUSH_SIZE = 10
/** 定时 flush 间隔（毫秒） */
const FLUSH_INTERVAL_MS = 30_000

/** 埋点附加参数 */
export interface TrackPayload {
  page?: string
  biz_type?: string
  biz_id?: number
  extra?: Record<string, unknown>
}

/** 内存事件队列 */
let queue: TrackEventItem[] = []
/** 定时器句柄（有事件入队才启动，flush 清空后停止） */
let timer: ReturnType<typeof setInterval> | null = null

/** 本地时间格式化为 Y-m-d H:i:s */
function formatLocalTime(date: Date): string {
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`
}

/** 当前页面路由（取页面栈栈顶，取不到则不传） */
function currentPageRoute(): string | undefined {
  const pages = getCurrentPages()
  return pages[pages.length - 1]?.route
}

/** 记录一条埋点事件（非阻塞，内部吞掉同步异常保证不影响业务） */
export function track(event: string, payload?: TrackPayload): void {
  try {
    const item: TrackEventItem = {
      event,
      page: payload?.page ?? currentPageRoute(),
      biz_type: payload?.biz_type,
      biz_id: payload?.biz_id,
      extra: payload?.extra,
      occurred_at: formatLocalTime(new Date())
    }
    queue.push(item)
    if (queue.length >= FLUSH_SIZE) {
      void flush()
      return
    }
    ensureTimer()
  } catch (err) {
    console.warn('[track] track error:', err)
  }
}

/** 批量上报当前队列（1~50 条/次，超出部分截断后留待下轮） */
export async function flush(): Promise<void> {
  if (!queue.length) return
  const batch = queue.splice(0, 50)
  stopTimer()
  try {
    await reportEvents(batch)
  } catch (err) {
    // 上报失败不打断业务，仅告警（丢弃本批，避免无限堆积）
    console.warn('[track] flush failed:', err)
  }
  if (queue.length) ensureTimer()
}

/** 空闲期定时 flush：每 30 秒触发一次 */
function ensureTimer(): void {
  if (timer) return
  timer = setInterval(() => {
    void flush()
  }, FLUSH_INTERVAL_MS)
}

function stopTimer(): void {
  if (timer) {
    clearInterval(timer)
    timer = null
  }
}
