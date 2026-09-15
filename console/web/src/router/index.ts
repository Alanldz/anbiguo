// 路由表 + 登录守卫
import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { STORAGE_TOKEN_KEY } from '@/constants'
import ConsoleLayout from '@/layout/ConsoleLayout.vue'

// 路由元信息类型增强
declare module 'vue-router' {
  interface RouteMeta {
    title?: string
    icon?: string
    group?: string
    permission?: string
    public?: boolean
  }
}

/**
 * 路由元信息：
 * - title: 菜单/面包屑标题
 * - permission: 所需权限码（留空表示登录即可访问）
 * - icon: 菜单图标名（element-plus-icons）
 * - group: 菜单分组（仅用于布局展示）
 */
export const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/login/index.vue'),
    meta: { title: '登录', public: true },
  },
  {
    path: '/',
    component: ConsoleLayout,
    redirect: '/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('@/views/dashboard/index.vue'),
        meta: { title: '学习概览', icon: 'Odometer', group: '学习中心', permission: 'console:dashboard' },
      },
      {
        path: 'banks',
        name: 'BankList',
        component: () => import('@/views/banks/index.vue'),
        meta: { title: '我的题库', icon: 'Collection', group: '题库管理', permission: 'console:bank:list' },
      },
      {
        path: 'banks/:id/questions',
        name: 'QuestionList',
        component: () => import('@/views/questions/index.vue'),
        meta: { title: '题目管理', icon: 'List', group: '题库管理', permission: 'console:question:list' },
      },
      {
        path: 'import',
        name: 'ImportList',
        component: () => import('@/views/import/index.vue'),
        meta: { title: '题库导入', icon: 'Upload', group: '题库管理', permission: 'console:import:create' },
      },
      {
        path: 'resources',
        name: 'ResourceList',
        component: () => import('@/views/resources/index.vue'),
        meta: { title: '学习资料', icon: 'Files', group: '我的资源', permission: 'console:file:list' },
      },
      {
        path: 'wrong',
        name: 'WrongList',
        component: () => import('@/views/wrong/index.vue'),
        meta: { title: '我的错题', icon: 'CircleClose', group: '我的资源', permission: 'console:wrong:list' },
      },
      {
        path: 'exams',
        name: 'ExamList',
        component: () => import('@/views/exams/index.vue'),
        meta: { title: '考试记录', icon: 'Trophy', group: '学习中心', permission: 'console:exam:list' },
      },
      {
        path: 'orders',
        name: 'OrderList',
        component: () => import('@/views/orders/index.vue'),
        meta: { title: '订单与会员', icon: 'ShoppingCart', group: '账号与订单', permission: 'console:order:list' },
      },
      {
        path: 'account',
        name: 'Account',
        component: () => import('@/views/account/index.vue'),
        meta: { title: '账号设置', icon: 'Setting', group: '账号与订单', permission: 'console:account:view' },
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/dashboard',
  },
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
})

// 登录守卫：未登录跳 /login
router.beforeEach((to) => {
  const token = localStorage.getItem(STORAGE_TOKEN_KEY)
  if (!to.meta.public && !token) {
    return { path: '/login' }
  }
  if (to.path === '/login' && token) {
    return { path: '/dashboard' }
  }
  return true
})

export default router
