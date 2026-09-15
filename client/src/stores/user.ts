import { defineStore } from 'pinia'
import { fetchProfile, fetchStudySummary } from '@/api/user'
import { loginByMobile } from '@/api/auth'
import { getStorage, setStorage, removeStorage, STORAGE_KEYS } from '@/utils/storage'
import type { StudySummary, UserProfile } from '@/types'

interface UserState {
  token: string
  profile: UserProfile | null
  summary: StudySummary | null
  loading: boolean
}

export const useUserStore = defineStore('user', {
  state: (): UserState => ({
    token: '',
    profile: null,
    summary: null,
    loading: false
  }),

  getters: {
    isLogged: (state) => Boolean(state.token),
    isVip: (state) => Boolean(state.profile && state.profile.member_level > 0)
  },

  actions: {
    /** 从本地恢复登录态（App onLaunch 调用） */
    restore() {
      this.token = getStorage<string>(STORAGE_KEYS.TOKEN, '') ?? ''
      this.profile = getStorage<UserProfile>(STORAGE_KEYS.USER_PROFILE, null) ?? null
    },

    /** 已登录则拉取最新资料 */
    async fetchProfileIfLogged() {
      if (!this.isLogged) return
      this.profile = await fetchProfile()
      setStorage(STORAGE_KEYS.USER_PROFILE, this.profile)
    },

    /** 手机号登录 */
    async login(mobile: string, code: string) {
      const result = await loginByMobile(mobile, code)
      this.token = result.token
      this.profile = result.user
      setStorage(STORAGE_KEYS.TOKEN, result.token)
      setStorage(STORAGE_KEYS.USER_PROFILE, result.user)
      return result
    },

    /** 拉取学习统计 */
    async loadSummary() {
      if (!this.isLogged) return
      this.loading = true
      try {
        this.summary = await fetchStudySummary()
      } finally {
        this.loading = false
      }
    },

    /** 退出登录 */
    logout() {
      this.token = ''
      this.profile = null
      this.summary = null
      removeStorage(STORAGE_KEYS.TOKEN)
      removeStorage(STORAGE_KEYS.USER_PROFILE)
    }
  }
})
