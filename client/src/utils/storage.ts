/**
 * 本地存储封装
 * 统一 key 前缀，避免与宿主环境冲突；所有读写都经过此处，禁止页面直接调 uni.setStorageSync。
 */

const STORAGE_PREFIX = 'ANBIGUO_'

/** 存储 key 统一登记（新增必须在此登记，避免散落魔法字符串） */
export const STORAGE_KEYS = {
  TOKEN: 'TOKEN',
  USER_PROFILE: 'USER_PROFILE',
  SEARCH_HISTORY: 'SEARCH_HISTORY',
  LAST_BANK_ID: 'LAST_BANK_ID',
  ANSWER_FONT_SIZE: 'ANSWER_FONT_SIZE'
} as const

type StorageKey = (typeof STORAGE_KEYS)[keyof typeof STORAGE_KEYS]

function buildKey(key: StorageKey): string {
  return STORAGE_PREFIX + key
}

export function setStorage<T>(key: StorageKey, value: T): void {
  try {
    uni.setStorageSync(buildKey(key), value)
  } catch (e) {
    console.error('[storage] set failed:', key, e)
  }
}

export function getStorage<T>(key: StorageKey, defaultValue?: T): T | undefined {
  try {
    const value = uni.getStorageSync(buildKey(key))
    return value === '' || value === null || value === undefined ? defaultValue : (value as T)
  } catch (e) {
    console.error('[storage] get failed:', key, e)
    return defaultValue
  }
}

export function removeStorage(key: StorageKey): void {
  try {
    uni.removeStorageSync(buildKey(key))
  } catch (e) {
    console.error('[storage] remove failed:', key, e)
  }
}

export function clearStorage(): void {
  Object.values(STORAGE_KEYS).forEach((key) => removeStorage(key as StorageKey))
}
