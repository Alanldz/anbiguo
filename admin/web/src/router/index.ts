// 路由表 + 登录守卫
import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { STORAGE_TOKEN_KEY } from '@/constants'
import AdminLayout from '@/layout/AdminLayout.vue'

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
    component: AdminLayout,
    redirect: '/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('@/views/dashboard/index.vue'),
        meta: { title: '仪表盘', icon: 'Odometer', group: '仪表盘' },
      },
      {
        path: 'bank/banks',
        name: 'BankList',
        component: () => import('@/views/bank/banks/index.vue'),
        meta: { title: '题库审核', icon: 'Collection', group: '内容管理', permission: 'bank:question-bank:audit' },
      },
      {
        path: 'content/banners',
        name: 'BannerList',
        component: () => import('@/views/content/banners/index.vue'),
        meta: { title: '轮播管理', icon: 'Picture', group: '内容管理', permission: 'content:banner:update' },
      },
      {
        path: 'content/imports',
        name: 'ImportList',
        component: () => import('@/views/content/imports/index.vue'),
        meta: { title: '导入任务', icon: 'Upload', group: '内容管理', permission: 'bank:question-bank:list' },
      },
      {
        path: 'bank/categories',
        name: 'BankCategory',
        component: () => import('@/views/bank/categories/index.vue'),
        meta: { title: '分类管理', icon: 'Collection', group: '题库管理', permission: 'bank:category:list' },
      },
      {
        path: 'order/orders',
        name: 'OrderList',
        component: () => import('@/views/order/orders/index.vue'),
        meta: { title: '订单管理', icon: 'Tickets', group: '交易管理', permission: 'order:order:list' },
      },
      {
        path: 'file/files',
        name: 'FileAsset',
        component: () => import('@/views/file/files/index.vue'),
        meta: { title: '文件资源', icon: 'Folder', group: '内容管理', permission: 'file:asset:list' },
      },
      {
        path: 'content/feedbacks',
        name: 'FeedbackList',
        component: () => import('@/views/content/feedbacks/index.vue'),
        meta: { title: '意见反馈', icon: 'ChatDotRound', group: '内容管理', permission: 'content:feedback:list' },
      },
      {
        path: 'user/users',
        name: 'UserList',
        component: () => import('@/views/user/users/index.vue'),
        meta: { title: '用户管理', icon: 'User', group: '用户管理', permission: 'sys:user:list' },
      },
      {
        path: 'system/admins',
        name: 'AdminList',
        component: () => import('@/views/system/admins/index.vue'),
        meta: { title: '管理员', icon: 'Avatar', group: '系统管理', permission: 'sys:admin:list' },
      },
      {
        path: 'system/roles',
        name: 'RoleList',
        component: () => import('@/views/system/roles/index.vue'),
        meta: { title: '角色权限', icon: 'Lock', group: '系统管理', permission: 'sys:role:update' },
      },
      {
        path: 'system/configs',
        name: 'ConfigList',
        component: () => import('@/views/system/configs/index.vue'),
        meta: { title: '配置中心', icon: 'Setting', group: '系统管理', permission: 'sys:config:view' },
      },
      {
        path: 'system/logs/operation',
        name: 'OperationLog',
        component: () => import('@/views/system/logs/operation.vue'),
        meta: { title: '操作日志', icon: 'Document', group: '系统管理', permission: 'sys:log:list' },
      },
      {
        path: 'system/logs/login',
        name: 'LoginLog',
        component: () => import('@/views/system/logs/login.vue'),
        meta: { title: '登录日志', icon: 'Clock', group: '系统管理', permission: 'sys:log:list' },
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
