// 鉴权状态仓库：token + 管理员信息 + 权限集
// 持久化到 localStorage（key: shitu_admin_token），刷新后可由 /auth/me 恢复
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { STORAGE_TOKEN_KEY } from '@/constants'
import type { AdminProfile } from '@/types/api.d'
import { fetchMe } from '@/api/auth'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string>(localStorage.getItem(STORAGE_TOKEN_KEY) || '')
  const admin = ref<AdminProfile | null>(null)

  /** 是否已登录 */
  const isLoggedIn = computed(() => !!token.value)

  /** 权限码集合（便于 hasPermission 判断） */
  const permissions = computed<Set<string>>(() => new Set(admin.value?.permissions ?? []))

  /** 角色名拼接 */
  const roleNames = computed(() => admin.value?.roles.map((r) => r.name).join('、') ?? '')

  /** 是否拥有某权限 */
  function hasPermission(code?: string): boolean {
    if (!code) return true // 未声明权限的菜单登录即可访问
    return permissions.value.has(code)
  }

  /** 设置登录信息 */
  function setAuth(newToken: string, profile: AdminProfile) {
    token.value = newToken
    admin.value = profile
    localStorage.setItem(STORAGE_TOKEN_KEY, newToken)
  }

  /** 清空登录信息 */
  function clearAuth() {
    token.value = ''
    admin.value = null
    localStorage.removeItem(STORAGE_TOKEN_KEY)
  }

  /** 拉取当前管理员信息（用于刷新后恢复会话） */
  async function loadProfile() {
    const profile = await fetchMe()
    admin.value = profile
    return profile
  }

  return { token, admin, isLoggedIn, permissions, roleNames, hasPermission, setAuth, clearAuth, loadProfile }
})
