// 后台框架：侧边栏（按权限过滤菜单）+ 顶栏（面包屑 / 管理员下拉 / 退出）
<template>
  <el-container class="admin-layout">
    <el-aside width="220px" class="admin-layout__aside">
      <div class="admin-layout__brand">
        <span class="admin-layout__brand-logo">识途刷题</span>
        <span class="admin-layout__brand-sub">总管理后台</span>
      </div>
      <el-menu
        :default-active="activeMenu"
        class="admin-layout__menu"
        background-color="#1f2937"
        text-color="#cbd5e1"
        active-text-color="#ffffff"
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
      <el-header class="admin-layout__header">
        <el-breadcrumb separator="/">
          <el-breadcrumb-item :to="{ path: '/dashboard' }">首页</el-breadcrumb-item>
          <el-breadcrumb-item v-for="crumb in breadcrumbs" :key="crumb.path">
            {{ crumb.title }}
          </el-breadcrumb-item>
        </el-breadcrumb>

        <el-dropdown class="admin-layout__user" @command="handleUserCommand">
          <span class="admin-layout__user-trigger">
            <el-icon><Avatar /></el-icon>
            <span>{{ auth.admin?.nickname || auth.admin?.username }}</span>
            <el-icon><ArrowDown /></el-icon>
          </span>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item disabled>{{ auth.roleNames }}</el-dropdown-item>
              <el-dropdown-item command="logout" divided>
                <el-icon><SwitchButton /></el-icon> 退出登录
              </el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </el-header>

      <el-main class="admin-layout__main">
        <router-view />
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessageBox, ElMessage } from 'element-plus'
import { useAuthStore } from '@/stores/auth'
import { logout } from '@/api/auth'
import type { RouteRecordRaw } from 'vue-router'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

// 从路由表收集需要展示的菜单（已按权限过滤）
const menuRoutes = computed(() =>
  (router.options.routes.find((r) => r.path === '/')?.children || []) as RouteRecordRaw[],
)

const visibleMenus = computed(() =>
  menuRoutes.value.filter((r) => auth.hasPermission(r.meta?.permission as string | undefined)),
)

const menuGroups = computed(() => {
  const set = new Set(visibleMenus.value.map((r) => r.meta?.group as string))
  return Array.from(set)
})

function menusByGroup(group: string) {
  return visibleMenus.value.filter((r) => r.meta?.group === group)
}

const activeMenu = computed(() => route.path)

const breadcrumbs = computed(() => {
  // 路由 path 与菜单 path 一致，直接取当前路由
  return [{ path: route.path, title: (route.meta.title as string) || '' }].filter((b) => b.title)
})

function handleUserCommand(command: string) {
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
</script>

<style scoped lang="scss">
.admin-layout {
  height: 100vh;

  &__aside {
    background-color: #1f2937;
    overflow: hidden;
  }

  &__brand {
    height: 60px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
    padding: 0 20px;
    color: #fff;
    border-bottom: 1px solid #374151;
  }

  &__brand-logo {
    font-size: 18px;
    font-weight: 700;
    color: #2563eb;
  }

  &__brand-sub {
    font-size: 12px;
    color: #9ca3af;
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
  color: #6b7280;
  padding-left: 20px;
}
</style>
