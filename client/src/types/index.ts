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

/**
 * 导入任务（API-IMP-002）
 * status 说明：后端 ImportTaskStatus 含「待校对」五态（1→pending / 2→parsing /
 * 3→proofread / 4→success / 5→failed），按契约放宽为 string，由页面做文案映射。
 */
export interface ImportTask {
  id: number
  bank_id: number
  origin_name: string
  total_count: number
  parsed_count: number
  status: string
  /** 状态文案（后端可下发，如「待校对」） */
  status_text?: string
  /** 解析进度（0~100），缺省时由 parsed_count / total_count 推算 */
  progress?: number
  fail_reason?: string
}

/** 练习模式，对应后端 PracticeMode 枚举（API-REC-001） */
export enum PracticeMode {
  Sequence = 1, // 顺序练习
  Random = 2, // 随机练习
  Special = 3, // 专项练习
  Wrong = 4, // 错题重做
  Flashcard = 5, // 闪卡
  Behead = 6 // 斩题
}

/** 练习记录状态，对应后端 PracticeStatus 枚举（API-REC-001） */
export enum PracticeStatus {
  Ongoing = 1, // 进行中
  Finished = 2, // 已完成
  Abandoned = 3 // 已放弃
}

/** 我的收藏项（API-FAV-001 /api/v1/favorites） */
export interface FavoriteItem {
  id: number // 收藏记录 id
  question_id: number // 题目 id
  bank_id: number // 所属题库 id
  bank_name: string // 所属题库名称
  question_type: QuestionType // 题型
  question_title: string // 题干
  question_options: QuestionOption[] // 选项
  question_difficulty: number // 难度（1~5）
  folder_name: string // 收藏夹名称
  created_at: string // 收藏时间
}

/** 我的笔记项（API-NOTE-001 /api/v1/notes） */
export interface NoteItem {
  id: number // 笔记记录 id
  question_id: number // 题目 id
  bank_id: number // 所属题库 id
  bank_name: string // 所属题库名称
  question_title: string // 关联题干摘要
  content: string // 笔记内容
  like_count: number // 点赞数
  created_at: string // 创建时间
  updated_at: string // 更新时间
}

/** 练习记录项（API-REC-001 /api/v1/practice-records） */
export interface PracticeRecordItem {
  id: number // 记录 id
  bank_id: number // 题库 id
  bank_name: string // 题库名称
  practice_mode: PracticeMode // 练习模式
  total_count: number // 题目总数
  answered_count: number // 已答题数
  right_count: number // 答对题数
  wrong_count: number // 答错题数
  correct_rate: number // 正确率（0~100）
  duration_seconds: number // 时长（秒）
  status: PracticeStatus // 状态
  started_at: string | null // 开始时间
  finished_at: string | null // 结束时间
}

/** 回收站题库项（API-BANK-008 /api/v1/question-banks/recycle），结构同 QuestionBank 并附加 deleted_at */
export interface RecycleBankItem extends QuestionBank {
  deleted_at: string // 删除时间
}

/** 消息通知类型，对应后端 NotificationType 枚举（API-MSG-001） */
export enum NotificationType {
  System = 1, // 系统通知
  Interaction = 2, // 互动通知
  Business = 3 // 业务通知
}

/** 消息通知项（API-MSG-001 /api/v1/notifications） */
export interface NotificationItem {
  id: number // 通知 id
  type: NotificationType // 通知类型
  title: string // 标题
  content: string // 内容
  biz_type: string // 关联业务类型（如 bank / member / report）
  biz_id: number // 关联业务 id
  is_read: 0 | 1 // 是否已读：0=未读 1=已读
  read_at: string | null // 已读时间
  created_at: string // 创建时间
}

/** 我的斩题项（API-MST-001 /api/v1/mastered-questions） */
export interface MasteredItem {
  id: number // 斩题记录 id
  question_id: number // 题目 id
  bank_id: number // 所属题库 id
  bank_name: string // 所属题库名称
  question_title: string // 题干
  question_type: QuestionType // 题型
  question_options: QuestionOption[] // 选项
  question_difficulty: number // 难度（1~5）
  wrong_count: number // 累计答错次数
  right_streak: number // 连续答对次数
  mastered_at: string // 斩掉时间
}

/** 易错题项（API-ERR-001 /api/v1/error-prone-questions） */
export interface ErrorProneItem {
  id: number // 记录 id
  bank_id: number // 所属题库 id
  question_title: string // 题干
  question_type: QuestionType // 题型
  question_options: QuestionOption[] // 选项
  question_difficulty: number // 难度（1~5）
  correct_rate: number // 全网正确率（百分比数值，如 32.5）
  answer_count: number // 答题人数
  is_wrong: boolean // 当前用户是否已在错题本
}

/** 试卷（API-EXM-001/002，questions 仅详情/组卷响应携带） */
export interface ExamPaper {
  id: number
  title: string
  bank_id: number
  duration_minutes: number
  total_score: number
  question_count: number
  questions?: Question[]
}

/** 考试记录（API-EXM-003/004/005） */
export interface ExamRecord {
  id: number
  paper_id: number
  title: string
  score: number
  total_score: number
  correct_count: number
  question_count: number
  cost_seconds: number
  created_at: string
}

/** 成绩详情逐题作答明细（API-EXM-004） */
export interface ExamRecordAnswer {
  question_id: number
  stem_preview: string // 题干摘要
  user_answer: string // 我的答案
  is_correct: boolean // 是否答对
  score: number // 本题得分
}

/** 成绩详情（API-EXM-004 成绩单 + 试卷回顾） */
export interface ExamRecordDetail extends ExamRecord {
  pass_score: number // 及格线
  is_passed: boolean // 是否及格
  answers: ExamRecordAnswer[] // 逐题作答明细
}

/** 题库市场条目（API-BANK-007 /api/v1/bank-market），在题库结构上扩展运营展示字段 */
export interface MarketBankItem extends QuestionBank {
  description: string // 简介
  usage_count: number // 使用人数（热度）
  is_recommend?: boolean // 是否推荐角标
}

/** 会员套餐（API-MBR-001 /api/v1/member/plans），MemberLevel：0 普通 1 月卡 2 季卡 3 年卡 4 永久 */
export interface MemberPlan {
  id: number
  name: string // 套餐名，如「连续包月」
  level: number // MemberLevel
  level_text: string // 等级文案
  duration_days: number // 有效天数，永久为 0
  price_amount: number // 现价（元）
  origin_amount: number // 原价（划线价）
  description: string
  benefits: string[] // 权益列表
  ai_import_quota: number // 赠送 AI 导题配额
  is_recommend: boolean // 是否推荐
}

/** 订单状态，对应后端 OrderStatus 枚举（API-ORD-001） */
export enum OrderStatus {
  Unpaid = 0, // 待支付
  Paid = 1, // 已支付
  Canceled = 2, // 已取消
  Refunded = 3, // 已退款
  Closed = 4 // 已关闭
}

/** 订单条目（API-ORD-001 /api/v1/orders） */
export interface OrderItem {
  id: number
  order_no: string // 订单号
  order_type: number // 订单类型：1 会员
  order_type_text: string // 订单类型文案
  biz_id: number // 关联业务 id（如套餐 id）
  biz_title: string // 商品名
  origin_amount: number // 原价
  discount_amount: number // 优惠金额
  pay_amount: number // 实付金额
  status: OrderStatus // 订单状态
  status_text: string // 状态文案
  created_at: string // 下单时间
  paid_at: string | null // 支付时间
}

/** 学习资料条目（API-FIL-003 /api/v1/file-assets，biz_type=3 学习资料） */
export interface ResourceItem {
  id: number
  bank_id: number | null // 所属题库，null 表示不限定
  file_name: string // 文件名（含扩展名）
  file_type: string // 类型（pdf/doc/mp4 等小写扩展名）
  file_size: number // 字节数
  url?: string // 下载/预览地址（私密文件为签名 URL）
  created_at: string // 上传时间
}

/** 搜索结果题（API-SRC-001 /api/v1/search/questions），Question 形状附加所属题库名 */
export interface SearchQuestionItem extends Question {
  bank_name?: string // 所属题库名称（服务端未下发时由前端兜底）
}

/** 意见反馈提交参数（API-FBK-001 POST /api/v1/feedbacks） */
export interface FeedbackSubmitParams {
  type: 1 | 2 | 3 // 反馈类型：1 功能异常 / 2 体验建议 / 3 其他
  content: string // 反馈内容（5~500 字）
  images?: number[] // 截图 file_assets id 数组（最多 9 张）
  contact?: string // 联系方式（选填，≤64 字）
}
