// 用户后台框架：浅色侧边栏（按权限过滤菜单）+ 顶栏（面包屑 / 用户下拉 / 退出）
<template>
  <el-container class="console-layout">
    <el-aside width="220px" class="console-layout__aside">
      <div class="console-layout__brand">
        <span class="console-layout__brand-logo">识途刷题</span>
        <span class="console-layout__brand-sub">用户后台</span>
      </div>
      <el-menu
        :default-active="activeMenu"
        class="console-layout__menu"
        background-color="#ffffff"
        text-color="#374151"
        active-text-color="#2563EB"
        router
      >
        <template v-for="group in menuGroups" :key="group">
          <el-menu-item-group :title="group">
            <el-menu-item
              v-for="item in menusByGroup(group)"
              :key="item.path"
              :index="item.path"
            >
              <el-icon v-if="item.meta?.icon"><component :is="item.meta.icon" /></el-icon>
              <span>{{ item.meta?.title }}</span>
            </el-menu-item>
          </el-menu-item-group>
        </template>
      </el-menu>
    </el-aside>

    <el-container>
      <el-header class="console-layout__header">
        <el-breadcrumb separator="/">
          <el-breadcrumb-item :to="{ path: '/dashboard' }">首页</el-breadcrumb-item>
          <el-breadcrumb-item v-for="crumb in breadcrumbs" :key="crumb.path">
            {{ crumb.title }}
          </el-breadcrumb-item>
        </el-breadcrumb>

        <el-dropdown class="console-layout__user" @command="handleUserCommand">
          <span class="console-layout__user-trigger">
            <el-avatar v-if="auth.user?.avatar" :src="auth.user.avatar" :size="28" />
            <el-icon v-else><Avatar /></el-icon>
            <span>{{ auth.user?.nickname || auth.user?.mobile }}</span>
            <el-icon><ArrowDown /></el-icon>
          </span>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item disabled>{{ auth.user?.mobile }}</el-dropdown-item>
              <el-dropdown-item command="account" divided>账号设置</el-dropdown-item>
              <el-dropdown-item command="logout" divided>
                <el-icon><SwitchButton /></el-icon> 退出登录
              </el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </el-header>

      <el-main class="console-layout__main">
        <router-view />
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessageBox, ElMessage } from 'element-plus'
import { useAuthStore } from '@/stores/auth'
import { logout } from '@/api/auth'
import type { RouteRecordRaw } from 'vue-router'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

// 从路由表收集需要展示的菜单
const menuRoutes = computed(() =>
  (router.options.routes.find((r) => r.path === '/')?.children || []) as RouteRecordRaw[],
)

// 用户后台为「单用户自管」场景：登录即可访问全部页面，permission 仅作登记与路由守卫用
const visibleMenus = computed(() => menuRoutes.value)

const menuGroups = computed(() => {
  const set = new Set(visibleMenus.value.map((r) => r.meta?.group as string))
  return Array.from(set)
})

function menusByGroup(group: string) {
  return visibleMenus.value.filter((r) => r.meta?.group === group)
}

// 题目管理页（/banks/:id/questions）高亮「我的题库」
const activeMenu = computed(() => {
  if (route.path.startsWith('/banks/')) return '/banks'
  return route.path
})

const breadcrumbs = computed(() => {
  return [{ path: route.path, title: (route.meta.title as string) || '' }].filter((b) => b.title)
})

function handleUserCommand(command: string) {
  if (command === 'account') {
    router.push('/account')
    return
  }
  if (command === 'logout') {
    ElMessageBox.confirm('确定退出登录吗？', '提示', {
      type: 'warning',
      confirmButtonText: '退出',
      cancelButtonText: '取消',
    })
      .then(async () => {
        try {
          await logout()
        } catch {
          // 忽略登出接口异常，仍执行本地清理
        }
        auth.clearAuth()
        ElMessage.success('已退出登录')
        router.replace('/login')
      })
      .catch(() => {})
  }
}

onMounted(async () => {
  // 已有 token 但无用户信息时（刷新后）恢复会话
  if (auth.isLoggedIn && !auth.user) {
    try {
      await auth.loadProfile()
    } catch {
      // 失效由拦截器统一处理
    }
  }
})
</script>

<style scoped lang="scss">
.console-layout {
  height: 100vh;

  &__aside {
    background-color: #ffffff;
    border-right: 1px solid #e5e7eb;
    overflow: hidden;
  }

  &__brand {
    height: 60px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
    padding: 0 20px;
    border-bottom: 1px solid #e5e7eb;
  }

  &__brand-logo {
    font-size: 18px;
    font-weight: 700;
    color: #2563eb;
  }

  &__brand-sub {
    font-size: 12px;
    color: #6b7280;
  }

  &__menu {
    border-right: none;
    height: calc(100vh - 60px);
    overflow-y: auto;
  }

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
  }

  &__user-trigger {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    outline: none;
  }

  &__main {
    background: #f5f7fa;
  }
}

:deep(.el-menu-item-group__title) {
  color: #9ca3af;
  padding-left: 20px;
  font-size: 12px;
}
</style>
