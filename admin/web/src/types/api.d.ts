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

/** 用户简要信息（订单/反馈中内嵌） */
export interface UserBrief {
  id: number
  nickname: string
  phone: string
}

/* ===================== ADM-100 分类管理 ===================== */
export interface CategoryItem {
  id: number
  parent_id: number
  name: string
  code: string
  icon: string
  level: number
  count: number
  sort_order: number
  status: number
  created_at: string
}

/** 新建分类（type 区分 bank/file，code 后端生成唯一） */
export interface CategoryPayload {
  type: 'bank' | 'file'
  parent_id: number
  name: string
  code: string
  icon: string
  sort_order: number
  status: number
}

/** 编辑分类（code 不可编辑） */
export interface CategoryUpdatePayload {
  name: string
  icon: string
  sort_order: number
  status: number
}

/** 前端组树后的节点 */
export interface CategoryTreeItem extends CategoryItem {
  children: CategoryTreeItem[]
}

/* ===================== ADM-101 配置连通性测试 ===================== */
export interface ConfigTestResult {
  ok: boolean
  message: string
  latency_ms: number
}

/* ===================== ADM-102 订单管理 ===================== */
export interface OrderItem {
  id: number
  order_no: string
  user: UserBrief
  order_type: number
  biz_id: number
  biz_title: string
  origin_amount: number
  discount_amount: number
  pay_amount: number
  pay_channel: number
  status: number
  client_platform: string
  remark: string
  created_at: string
  paid_at: string
}

/** 订单列表返回（与全局分页约定一致：pagination 包裹） */
export type OrderListData = PaginatedData<OrderItem>

export interface OrderQuery {
  page?: number
  page_size?: number
  order_no?: string
  keyword?: string
  order_type?: number
  status?: number
}

export interface OrderRefundPayload {
  reason: string
}

/* ===================== ADM-103 文件资源管理 ===================== */
export interface FileAssetItem {
  id: number
  user_id: number
  biz_type: number
  bank_id: number
  category_id: number
  origin_name: string
  object_key: string
  file_ext: string
  file_size: number
  mime_type: string
  storage: string
  is_public: number
  created_at: string
}

export type FileListData = PaginatedData<FileAssetItem>

export interface FileQuery {
  page?: number
  page_size?: number
  keyword?: string
  biz_type?: number
  user_id?: number
  storage?: string
}

/* ===================== ADM-104 意见反馈 ===================== */
export interface FeedbackItem {
  id: number
  user: UserBrief
  type: number
  content: string
  images: string[]
  contact: string
  status: number
  reply: string
  handler_id: number
  handled_at: string
  created_at: string
}

export type FeedbackListData = PaginatedData<FeedbackItem>

export interface FeedbackQuery {
  page?: number
  page_size?: number
  status?: number
  type?: number
  keyword?: string
}

export interface FeedbackHandlePayload {
  status: 1 | 2
  reply: string
}

/* ===================== ADM-105 会员套餐 ===================== */
export interface MemberPlanItem {
  id: number
  name: string
  level: number
  duration_days: number
  price_amount: number
  origin_amount: number
  description: string
  benefits: string[]
  ai_import_quota: number
  is_recommend: number
  sort_order: number
  status: number
  created_at: string
}

/** 编辑会员套餐（任意子集） */
export type MemberPlanUpdatePayload = Partial<{
  name: string
  level: number
  duration_days: number
  price_amount: number
  origin_amount: number
  description: string
  benefits: string[]
  ai_import_quota: number
  is_recommend: number
  sort_order: number
  status: number
}>
