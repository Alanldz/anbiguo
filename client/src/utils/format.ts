/**
 * 通用格式化工具
 */

/** 时间格式化，默认 YYYY-MM-DD */
export function formatDate(input: string | number | Date, pattern = 'YYYY-MM-DD'): string {
  const date = input instanceof Date ? input : new Date(input)
  if (Number.isNaN(date.getTime())) return ''

  const map: Record<string, string> = {
    YYYY: String(date.getFullYear()),
    MM: padZero(date.getMonth() + 1),
    DD: padZero(date.getDate()),
    HH: padZero(date.getHours()),
    mm: padZero(date.getMinutes()),
    ss: padZero(date.getSeconds())
  }
  return pattern.replace(/YYYY|MM|DD|HH|mm|ss/g, (key) => map[key] ?? key)
}

function padZero(value: number): string {
  return value < 10 ? `0${value}` : String(value)
}

/** 大数字缩写：12000 → 1.2万 */
export function formatCount(value: number): string {
  if (value < 10000) return String(value)
  if (value < 100000000) return `${(value / 10000).toFixed(1)}万`
  return `${(value / 100000000).toFixed(1)}亿`
}

/** 秒数转 mm:ss（考试倒计时用） */
export function formatDuration(totalSeconds: number): string {
  const safe = Math.max(0, Math.floor(totalSeconds))
  const minutes = Math.floor(safe / 60)
  const seconds = safe % 60
  return `${padZero(minutes)}:${padZero(seconds)}`
}

/** 正确率计算，返回保留一位小数的百分数字符串 */
export function formatAccuracy(correct: number, total: number): string {
  if (!total) return '0.0'
  return ((correct / total) * 100).toFixed(1)
}

/** 手机号脱敏：13800001111 → 138****1111 */
export function maskMobile(mobile: string): string {
  if (!mobile || mobile.length < 7) return mobile
  return `${mobile.slice(0, 3)}****${mobile.slice(-4)}`
}
