// 识途刷题 · 总后台 API 契约 TypeScript 类型定义
// 对应 docs/04 §五 总后台接口登记表（/admin-api/v1）

/** 统一响应结构 */
export interface ApiResponse<T = unknown> {
  code: number
  message: string
  data: T
  request_id: string
  timestamp: number
}

/** 分页数据结构 */
export interface Pagination {
  page: number
  page_size: number
  total: number
  total_pages: number
}

export interface PaginatedData<T> {
  list: T[]
  pagination: Pagination
}

/** 角色简要信息 */
export interface RoleBrief {
  id: number
  name: string
  code?: string
}

/** 权限码（字符串数组） */
export type PermissionCode = string

/** 登录 / 我的信息 返回 */
export interface AdminProfile {
  id: number
  username: string
  nickname: string
  roles: RoleBrief[]
  permissions: PermissionCode[]
}

export interface LoginData {
  token: string
  expires_in: number
  admin: AdminProfile
}

/** 仪表盘统计 */
export interface DashboardSummary {
  user_total: number
  user_new_today: number
  bank_total: number
  bank_pending_audit: number
  question_total: number
  import_running: number
  login_7d: LoginTrendItem[]
}

export interface LoginTrendItem {
  date: string
  count: number
}

/** 管理员 */
export interface AdminItem {
  id: number
  username: string
  nickname: string
  status: number
  last_login_at: string | null
  roles: RoleBrief[]
}

export interface AdminCreatePayload {
  username: string
  nickname: string
  password: string
  role_ids: number[]
  status: number
}

export interface AdminUpdatePayload {
  nickname?: string
  password?: string
  role_ids?: number[]
  status?: number
}

/** 角色 */
export interface RoleItem {
  id: number
  name: string
  code: string
  description?: string
  permission_ids: number[]
}

export interface RolePayload {
  name: string
  code: string
  description?: string
  permission_ids: number[]
}

/** 权限树节点 */
export interface PermissionNode {
  id: number
  name: string
  code: string
  pid: number
  children: PermissionNode[]
}

/** 配置项 */
export interface ConfigGroup {
  code: string
  name: string
}

export interface ConfigItem {
  id: number
  group_code: string
  config_key: string
  value: string
  is_secret: number
  value_type: string
  description: string
  updated_at: string
}

export interface ConfigListData {
  groups: ConfigGroup[]
  list: ConfigItem[]
}

export interface ConfigUpdatePayload {
  value: string
}

/** 操作日志 */
export interface OperationLogItem {
  id: number
  admin_id: number
  admin_name?: string
  action: string
  module?: string
  target?: string
  ip?: string
  created_at: string
}

/** 登录日志 */
export interface LoginLogItem {
  id: number
  admin_id: number
  admin_name?: string
  ip?: string
  user_agent?: string
  success: number
  created_at: string
}

/** 用户 */
export interface UserItem {
  id: number
  mobile: string
  nickname: string
  member_level: number
  status: number
  created_at: string
}

export interface UserStatusPayload {
  status: number
}

/** 题库 */
export interface BankItem {
  id: number
  title: string
  status: number
  source_type: number
  question_count: number
  user_mobile: string
  created_at: string
}

export interface BankAuditPayload {
  status: number
  remark: string
}

export interface BankStatusPayload {
  status: number
}

/** 轮播 */
export interface BannerItem {
  id: number
  title: string
  image: string
  position: string
  link_type: number
  link_value: string
  sort: number
  status: number
  start_at: string
  end_at: string
}

export interface BannerPayload {
  title: string
  image: string
  position: string
  link_type: number
  link_value: string
  sort: number
  status: number
  start_at: string
  end_at: string
}

/** 导入任务 */
export interface ImportTaskItem {
  id: number
  bank_id?: number
  bank_title?: string
  status: number
  total_count?: number
  parsed_count?: number
  admin_id?: number
  created_at: string
  updated_at: string
}

/** 通用列表查询参数 */
export interface ListQuery {
  page?: number
  page_size?: number
  keyword?: string
  status?: number
}
