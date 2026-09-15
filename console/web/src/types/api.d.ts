// 识途刷题 · 用户电脑端后台（console）API 契约 TypeScript 类型定义
// 唯一依据：console/API-CONTRACT.md（基路径 /console-api/v1）

/** 统一响应结构：code=0 为成功 */
export interface ApiResponse<T = unknown> {
  code: number
  message: string
  data: T
  request_id: string
  timestamp: number
}

/** 分页元信息 */
export interface Pagination {
  page: number
  page_size: number
  total: number
  total_pages: number
}

/** 分页数据结构 */
export interface PaginatedData<T> {
  list: T[]
  pagination: Pagination
}

// ---------------------------------------------------------------- 公共对象

/** 会员简要信息（内嵌于 UserBrief） */
export interface MemberBrief {
  level: number
  level_text: string
  expired_at: string | null
}

/** 当前登录用户 UserBrief */
export interface UserBrief {
  id: number
  uid: string
  mobile: string
  nickname: string
  avatar: string
  member: MemberBrief | null
}

/** API-CSL-AUTH-001 登录返回 */
export interface LoginData {
  token: string
  expires_in: number
  user: UserBrief
}

/** API-CSL-AUTH-004 短信验证码返回（生产环境 debug_code 恒为 null） */
export interface SmsCodeData {
  debug_code: string | null
}

/** 树形分类节点（题库分类 / 资料分类通用结构） */
export interface CategoryNode {
  id: number
  parent_id: number
  name: string
  code: string
  level: number
  children?: CategoryNode[]
}

// ---------------------------------------------------------- 二、学习概览

/** 学习概览 summary */
export interface StatisticsSummary {
  /** 我的题库数 */
  bank_count: number
  /** 我的题目总数 */
  question_count: number
  /** 累计练习次数 */
  practice_count: number
  /** 累计考试次数 */
  exam_count: number
  /** 累计作答题数 */
  answer_count: number
  right_count: number
  wrong_count: number
  /** 正确率（字符串，如 "78.85"） */
  correct_rate: string
  duration_seconds: number
  study_days: number
  /** 错题本在册数 */
  wrong_question_count: number
  favorite_count: number
  note_count: number
}

/** 会员信息（统计概览 / API-CSL-ORD-003 共用） */
export interface MemberInfo {
  level: number
  level_text: string
  status: number
  status_text: string
  started_at?: string | null
  expired_at: string | null
  ai_import_quota: number
  source_type?: number
  source_type_text?: string
}

/** 近 30 天趋势项 */
export interface TrendItem {
  date: string
  answer_count: number
  right_count: number
  duration_seconds: number
}

/** API-CSL-STAT-001 学习概览 */
export interface StatisticsOverview {
  summary: StatisticsSummary
  member: MemberInfo
  trend: TrendItem[]
}

// ---------------------------------------------------------- 三、我的题库

/** 题库列表项 BankItem */
export interface BankItem {
  id: number
  title: string
  subtitle: string
  cover: string
  category_id: number
  category_name: string
  /** 1用户上传 / 2官方 / 3购买 / 4AI生成 */
  source_type: number
  source_type_text: string
  /** 1免费 / 2会员免费 / 3单独购买 */
  charge_type: number
  charge_type_text: string
  question_count: number
  chapter_count: number
  practice_count: number
  user_count: number
  /** 1正常 / 2隐藏 / 3待审核 / 4已拒绝 */
  status: number
  status_text: string
  tags: string[]
  created_at: string
  updated_at: string
}

/** API-CSL-BANK-001 查询参数 */
export interface BankListQuery {
  page?: number
  page_size?: number
  keyword?: string
  category_id?: number
  source_type?: number
  status?: number
}

/** API-CSL-BANK-003 新建题库入参 */
export interface BankCreatePayload {
  title: string
  subtitle?: string
  category_id?: number
  cover?: string
  charge_type?: number
}

/** API-CSL-BANK-004 更新题库入参（字段全部可选） */
export type BankUpdatePayload = Partial<BankCreatePayload>

/** 导出结构中的选项 */
export interface ExportQuestionOption {
  option_key: string
  content: string
  is_correct: number
}

/** 导出结构中的题目 */
export interface ExportQuestion {
  question_type: number
  stem: string
  analysis: string
  answer: string
  difficulty: number
  score: string
  options: ExportQuestionOption[]
}

/** API-CSL-BANK-006 导出返回 */
export interface BankExportData {
  bank: { id: number; title: string }
  exported_at: string
  questions: ExportQuestion[]
}

// -------------------------------------------------------------- 四、章节

/** 章节 ChapterItem */
export interface ChapterItem {
  id: number
  bank_id: number
  parent_id: number
  name: string
  level: number
  question_count: number
  sort_order: number
}

/** API-CSL-CHP-002 新建章节入参 */
export interface ChapterCreatePayload {
  name: string
  parent_id?: number
  sort_order?: number
}

/** API-CSL-CHP-003 更新章节入参 */
export interface ChapterUpdatePayload {
  name?: string
  sort_order?: number
}

// ------------------------------------------------------------ 五、题目管理

/** 题目选项（含详情返回的 id / sort_order） */
export interface QuestionOption {
  id?: number
  option_key: string
  content: string
  /** 1 正确 / 0 错误 */
  is_correct: number
  sort_order?: number
}

/** 题目列表项 QuestionItem */
export interface QuestionItem {
  id: number
  bank_id: number
  chapter_id: number
  chapter_name: string
  /** 1单选 / 2多选 / 3判断 / 4填空 / 5简答 */
  question_type: number
  question_type_text: string
  stem: string
  stem_preview: string
  answer: string
  /** 1易 / 2中 / 3难 */
  difficulty: number
  difficulty_text: string
  /** 分值（字符串，如 "2.00"） */
  score: string
  status: number
  /** 1手动录入 / 2文档导入 / 3拍照OCR / 4AI生成 */
  source_type: number
  source_type_text: string
  answer_count: number
  right_count: number
  correct_rate: string
  sort_order: number
  created_at: string
}

/** API-CSL-QST-002 题目详情（含解析与选项） */
export interface QuestionDetail extends QuestionItem {
  analysis: string
  options: QuestionOption[]
}

/** API-CSL-QST-003 新增题目入参 */
export interface QuestionCreatePayload {
  question_type: number
  stem: string
  analysis?: string
  answer?: string
  difficulty?: number
  score?: number
  chapter_id?: number
  sort_order?: number
  /** 单选 / 多选 / 判断必须传；填空 / 简答传空数组 */
  options: QuestionOption[]
}

/** API-CSL-QST-004 更新题目入参 */
export type QuestionUpdatePayload = Partial<QuestionCreatePayload>

/** API-CSL-QST-001 查询参数 */
export interface QuestionListQuery {
  page?: number
  page_size?: number
  keyword?: string
  question_type?: number
  difficulty?: number
  chapter_id?: number
  status?: number
}

/** API-CSL-QST-006 批量删除入参 */
export interface QuestionBatchDeletePayload {
  ids: number[]
}

/** API-CSL-QST-007 批量移动章节入参 */
export interface QuestionBatchMovePayload {
  ids: number[]
  chapter_id: number
}

// ------------------------------------------------------------ 六、题库导入

/** 导入任务 ImportTaskItem */
export interface ImportTaskItem {
  id: number
  task_no: string
  bank_id: number
  bank_title: string
  origin_name: string
  file_ext: string
  /** 1文档导入 / 2手动录入 / 3拍照OCR / 4试题答案分离 */
  import_mode: number
  import_mode_text: string
  total_count: number
  success_count: number
  fail_count: number
  /** 进度 0 ~ 100 */
  progress: number
  /** 1待解析 / 2解析中 / 3待校对 / 4已完成 / 5失败 */
  status: number
  status_text: string
  error_message: string
  started_at: string | null
  finished_at: string | null
  created_at: string
}

/** 待校对题目（结构与 QST 新增入参一致） */
export type ImportQuestionDraft = QuestionCreatePayload

/** API-CSL-IMP-003 导入任务详情 */
export interface ImportTaskDetail extends ImportTaskItem {
  result: ImportQuestionDraft[]
}

/** API-CSL-IMP-001 创建导入任务入参 */
export interface ImportTaskCreatePayload {
  file_id: string
  /** 传 0 且带 bank_title 时先建题库 */
  bank_id?: number
  bank_title?: string
  import_mode?: number
}

/** API-CSL-IMP-001 创建导入任务返回 */
export interface ImportTaskCreateResult {
  id: number
  task_no: string
  status: number
  status_text: string
  bank_id: number
}

/** API-CSL-IMP-005 导入模板列说明 */
export interface ImportTemplateColumn {
  name: string
  required: boolean
  desc: string
}

/** API-CSL-IMP-005 导入模板说明 */
export interface ImportTemplate {
  columns: ImportTemplateColumn[]
  sample_url: string
}

/** API-CSL-IMP-002 查询参数 */
export interface ImportTaskListQuery {
  page?: number
  page_size?: number
  status?: number
}

// ------------------------------------------------------------ 七、学习资料

/** 资料分类 FileCategoryItem */
export interface FileCategoryItem {
  id: number
  parent_id: number
  name: string
  code: string
  file_count: number
  sort_order: number
  children?: FileCategoryItem[]
}

/** API-CSL-FIL-002/003 分类入参 */
export interface FileCategoryPayload {
  name?: string
  parent_id?: number
  sort_order?: number
}

/** 资料 FileAssetItem */
export interface FileAssetItem {
  id: number
  category_id: number
  category_name: string
  /** 1题库源文件 / 2题目图片 / 3学习资料 / 4课程音视频 / 5头像 / 6公开静态 / 9临时文件 */
  biz_type: number
  biz_type_text: string
  origin_name: string
  file_ext: string
  file_size: number
  file_size_text: string
  mime_type: string
  is_public: number
  /** 1待上传 / 2已上传 / 3解析中 / 4已归档 / 5失败 */
  status: number
  status_text: string
  created_at: string
}

/** API-CSL-FIL-006 上传后登记入参 */
export interface FileAssetRegisterPayload {
  object_key: string
  origin_name: string
  file_ext?: string
  file_size?: number
  file_hash?: string
  mime_type?: string
  category_id?: number
  biz_type?: number
  is_public?: number
}

/** API-CSL-FIL-005 查询参数 */
export interface FileAssetListQuery {
  page?: number
  page_size?: number
  keyword?: string
  category_id?: number
  biz_type?: number
}

/** API-CSL-FIL-009 直传凭证入参 */
export interface FileUploadTokenPayload {
  biz_type: number
  file_ext: string
  file_name: string
  category_id?: number
}

/** API-CSL-FIL-009 直传凭证返回 */
export interface FileUploadToken {
  provider: string
  upload_token: string
  upload_url: string
  object_key: string
  expires_in: number
  max_size: number
}

/** API-CSL-FIL-008 下载地址 */
export interface FileDownloadUrl {
  url: string
  expires_in: number
}

// ------------------------------------------------------------ 八、我的错题

/** 错题 WrongQuestionItem */
export interface WrongQuestionItem {
  id: number
  question_id: number
  bank_id: number
  bank_title: string
  question_type: number
  question_type_text: string
  stem_preview: string
  wrong_count: number
  last_wrong_at: string
  last_answer: string
  /** 1在错题本 / 2已移除 / 3已掌握 */
  status: number
  status_text: string
}

/** API-CSL-WRG-001 查询参数 */
export interface WrongQuestionListQuery {
  page?: number
  page_size?: number
  bank_id?: number
  keyword?: string
}

/** API-CSL-WRG-003 批量移除入参 */
export interface WrongBatchRemovePayload {
  ids: number[]
}

// ------------------------------------------------------------ 九、考试记录

/** 考试记录 ExamRecordItem */
export interface ExamRecordItem {
  id: number
  record_no: string
  paper_title: string
  bank_id: number
  bank_title: string
  total_count: number
  right_count: number
  wrong_count: number
  correct_rate: string
  get_score: string
  total_score: string
  duration_seconds: number
  /** 1 及格 / 0 不及格 */
  is_passed: number
  /** 1进行中 / 2已交卷 / 3超时自动交卷 / 4已作废 */
  status: number
  status_text: string
  submitted_at: string | null
  created_at: string
}

/** 作答明细项 */
export interface ExamAnswerItem {
  question_id: number
  stem_preview: string
  user_answer: string
  is_correct: number
  score: string
}

/** API-CSL-EXM-002 考试记录详情 */
export interface ExamRecordDetail extends ExamRecordItem {
  answers: ExamAnswerItem[]
}

/** API-CSL-EXM-001 查询参数 */
export interface ExamRecordListQuery {
  page?: number
  page_size?: number
  bank_id?: number
  keyword?: string
}

// ---------------------------------------------------------- 十、订单与会员

/** 订单 OrderItem */
export interface OrderItem {
  id: number
  order_no: string
  /** 1会员 / 2题库购买 / 3学习资料购买 */
  order_type: number
  order_type_text: string
  biz_title: string
  /** 金额均为字符串，保留 2 位小数 */
  origin_amount: string
  discount_amount: string
  pay_amount: string
  /** 1微信支付 / 2支付宝 */
  pay_channel: number
  pay_channel_text: string
  /** 0待支付 / 1已支付 / 2已取消 / 3已退款 / 4已关闭 */
  status: number
  status_text: string
  paid_at: string | null
  created_at: string
}

/** API-CSL-ORD-001 查询参数 */
export interface OrderListQuery {
  page?: number
  page_size?: number
  status?: number
  order_type?: number
}

/** 会员套餐 MemberPlan */
export interface MemberPlan {
  id: number
  name: string
  level: number
  level_text: string
  duration_days: number
  price_amount: string
  origin_amount: string
  description: string
  benefits: string[]
  ai_import_quota: number
  is_recommend: number
}

// ---------------------------------------------------------- 十一、账号设置

/** API-CSL-ACC-001 个人资料 */
export interface ProfileInfo {
  id: number
  uid: string
  mobile: string
  nickname: string
  avatar: string
  /** 0未知 / 1男 / 2女 */
  gender: number
  birthday: string
  province: string
  city: string
  exam_target: string
  bio: string
  study_days: number
  study_seconds: number
  created_at: string
}

/** API-CSL-ACC-002 更新资料入参 */
export interface ProfileUpdatePayload {
  nickname?: string
  avatar?: string
  gender?: number
  birthday?: string
  province?: string
  city?: string
  exam_target?: string
  bio?: string
}

/** API-CSL-ACC-003 修改密码入参 */
export interface PasswordUpdatePayload {
  old_password?: string
  new_password: string
}

/** API-CSL-ACC-004 换绑手机入参 */
export interface MobileUpdatePayload {
  new_mobile: string
  code: string
}
