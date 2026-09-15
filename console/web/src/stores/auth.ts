// 鉴权状态仓库：token + 当前用户(UserBrief)
// 持久化到 localStorage（key: shitu_console_token），刷新后可由 /auth/me 恢复
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { STORAGE_TOKEN_KEY } from '@/constants'
import type { UserBrief } from '@/types/api.d'
import { fetchMe } from '@/api/auth'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string>(localStorage.getItem(STORAGE_TOKEN_KEY) || '')
  const user = ref<UserBrief | null>(null)

  /** 是否已登录 */
  const isLoggedIn = computed(() => !!token.value)

  /** 设置登录信息 */
  function setAuth(newToken: string, profile: UserBrief) {
    token.value = newToken
    user.value = profile
    localStorage.setItem(STORAGE_TOKEN_KEY, newToken)
  }

  /** 清空登录信息 */
  function clearAuth() {
    token.value = ''
    user.value = null
    localStorage.removeItem(STORAGE_TOKEN_KEY)
  }

  /** 拉取当前用户信息（用于刷新后恢复会话） */
  async function loadProfile() {
    const profile = await fetchMe()
    user.value = profile
    return profile
  }

  return { token, user, isLoggedIn, setAuth, clearAuth, loadProfile }
})
