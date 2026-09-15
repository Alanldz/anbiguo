/**
 * 全局类型定义
 * 规则：所有接口出入参类型统一放此处或 src/types/ 下的分模块文件，
 *       禁止在页面里内联定义 interface（便于复用与统一维护）。
 */

/** 统一响应结构，与 docs/04-API接口规范与登记表.md §2.2 一致 */
export interface ApiResponse<T = unknown> {
  code: number
  message: string
  data: T
  request_id?: string
  timestamp?: number
}

/** 分页数据结构 */
export interface Pagination {
  page: number
  page_size: number
  total: number
  total_pages: number
}

/** 分页响应数据 */
export interface PageData<T> {
  list: T[]
  pagination: Pagination
}

/** 分页请求参数 */
export interface PageParams {
  page?: number
  page_size?: number
}

/** 题库来源类型，对应后端 BankSourceType 枚举 */
export enum BankSourceType {
  Upload = 1,
  Official = 2,
  Purchased = 3,
  AiGenerated = 4
}

/** 题库状态，对应后端 BankStatus 枚举 */
export enum BankStatus {
  Normal = 1,
  Hidden = 2,
  Auditing = 3,
  Rejected = 4
}

/** 题型，对应后端 QuestionType 枚举 */
export enum QuestionType {
  Single = 1,
  Multiple = 2,
  Judge = 3,
  Blank = 4,
  Essay = 5
}

/** 文件业务类型，对应后端 FileBizType 枚举 */
export enum FileBizType {
  BankSource = 1,
  QuestionImage = 2,
  StudyResource = 3,
  CourseMedia = 4,
  Avatar = 5,
  PublicStatic = 6,
  Temp = 9
}

/** 用户资料 */
export interface UserProfile {
  id: number
  nickname: string
  avatar: string
  mobile: string
  uid: string
  is_creator: boolean
  member_level: number
  member_expired_at: string | null
}

/** 登录结果 */
export interface LoginResult {
  token: string
  expires_in: number
  user: UserProfile
}

/** 题库分类 */
export interface BankCategory {
  id: number
  name: string
  code: string
  icon?: string
}

/** 题库 */
export interface QuestionBank {
  id: number
  title: string
  category_id: number
  source_type: BankSourceType
  question_count: number
  practiced_count: number
  status: BankStatus
  created_at: string
}

/** 题目选项 */
export interface QuestionOption {
  key: string
  content: string
}

/** 题目 */
export interface Question {
  id: number
  bank_id: number
  type: QuestionType
  title: string
  options: QuestionOption[]
  answer: string
  analysis: string
  is_favorited: boolean
  note: string
}

/** 学习空间统计 */
export interface StudySummary {
  practice_count: number
  accuracy: number
  wrong_count: number
  favorite_count: number
  study_minutes: number
}

/** 导入任务 */
export interface ImportTask {
  id: number
  bank_id: number
  origin_name: string
  total_count: number
  parsed_count: number
  status: 'pending' | 'parsing' | 'success' | 'failed'
  fail_reason?: string
}
