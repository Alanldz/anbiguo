/**
 * 接口层全局配置
 * USE_MOCK = true  → 返回 src/mock 下的模拟数据（后端未就绪时使用）
 * USE_MOCK = false → 走真实接口（联调/生产）
 * 联调时只需改这一处，业务代码零改动。
 */
export const USE_MOCK = true

/** Mock 模拟网络延迟（毫秒），用于验证 loading 与骨架屏表现 */
export const MOCK_DELAY = 300

export function mockDelay<T>(data: T, delay = MOCK_DELAY): Promise<T> {
  return new Promise((resolve) => setTimeout(() => resolve(data), delay))
}
