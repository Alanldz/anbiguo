/**
 * 用户相关接口
 * 接口编号见 docs/04-API接口规范与登记表.md §三
 */

import { http } from '@/utils/request'
import { USE_MOCK, mockDelay } from './config'
import { mockStudySummary, mockUserProfile } from '@/mock'
import type { StudySummary, UserProfile } from '@/types'

/** API-USER-001 获取个人资料 */
export function fetchProfile(): Promise<UserProfile> {
  if (USE_MOCK) return mockDelay(mockUserProfile)
  return http.get<UserProfile>('/api/v1/user/profile')
}

/** API-USER-002 更新个人资料 */
export function updateProfile(data: Partial<Pick<UserProfile, 'nickname' | 'avatar'>>): Promise<void> {
  return http.put<void>('/api/v1/user/profile', data)
}

/** API-USER-003 我的学习空间统计 */
export function fetchStudySummary(): Promise<StudySummary> {
  if (USE_MOCK) return mockDelay(mockStudySummary)
  return http.get<StudySummary>('/api/v1/user/study-summary')
}
