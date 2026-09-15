// 识途刷题 · 用户电脑端后台 全局常量
// 集中管理状态映射等，禁止在页面散落魔法值

/** 统一的本地存储 key（用户后台） */
export const STORAGE_TOKEN_KEY = 'shitu_console_token'

/** 登录失效错误码（跳登录页） */
export const CODE_UNAUTHORIZED = 10401
/** 无权限错误码 */
export const CODE_FORBIDDEN = 10403
/** 成功状态码 */
export const CODE_SUCCESS = 0

/** 默认分页大小 */
export const DEFAULT_PAGE_SIZE = 20

/** 分页大小选项 */
export const PAGE_SIZE_OPTIONS = [10, 20, 50, 100]

/** 菜单分组与路由元信息标识（permission 留空表示登录即可访问） */

// ---- 题库相关枚举 ----
/** 题库来源：1用户上传 / 2官方 / 3购买 / 4AI生成 */
export const BANK_SOURCE_MAP: Record<number, string> = {
  1: '用户上传',
  2: '官方',
  3: '购买',
  4: 'AI生成',
}

/** 题库收费类型：1免费 / 2会员免费 / 3单独购买 */
export const BANK_CHARGE_MAP: Record<number, string> = {
  1: '免费',
  2: '会员免费',
  3: '单独购买',
}

/** 题库状态：1正常 / 2隐藏 / 3待审核 / 4已拒绝 */
export const BANK_STATUS_MAP: Record<number, { label: string; type: '' | 'success' | 'info' | 'warning' | 'danger' }> = {
  1: { label: '正常', type: 'success' },
  2: { label: '隐藏', type: 'info' },
  3: { label: '待审核', type: 'warning' },
  4: { label: '已拒绝', type: 'danger' },
}

// ---- 题目相关枚举 ----
/** 题型：1单选 / 2多选 / 3判断 / 4填空 / 5简答 */
export const QUESTION_TYPE_MAP: Record<number, string> = {
  1: '单选',
  2: '多选',
  3: '判断',
  4: '填空',
  5: '简答',
}

/** 难度：1易 / 2中 / 3难 */
export const QUESTION_DIFFICULTY_MAP: Record<number, { label: string; type: '' | 'success' | 'warning' | 'danger' }> = {
  1: { label: '易', type: 'success' },
  2: { label: '中', type: 'warning' },
  3: { label: '难', type: 'danger' },
}

/** 题目来源：1手动录入 / 2文档导入 / 3拍照OCR / 4AI生成 */
export const QUESTION_SOURCE_MAP: Record<number, string> = {
  1: '手动录入',
  2: '文档导入',
  3: '拍照OCR',
  4: 'AI生成',
}

// ---- 导入任务枚举 ----
/** 导入方式：1文档导入 / 2手动录入 / 3拍照OCR / 4试题答案分离 */
export const IMPORT_MODE_MAP: Record<number, string> = {
  1: '文档导入',
  2: '手动录入',
  3: '拍照OCR',
  4: '试题答案分离',
}

/** 导入任务状态：1待解析 / 2解析中 / 3待校对 / 4已完成 / 5失败 */
export const IMPORT_TASK_STATUS_MAP: Record<number, { label: string; type: '' | 'success' | 'info' | 'warning' | 'danger' }> = {
  1: { label: '待解析', type: 'info' },
  2: { label: '解析中', type: 'warning' },
  3: { label: '待校对', type: 'warning' },
  4: { label: '已完成', type: 'success' },
  5: { label: '失败', type: 'danger' },
}

// ---- 资源文件枚举 ----
/** 文件业务类型：1题库源文件 / 2题目图片 / 3学习资料 / 4课程音视频 / 5头像 / 6公开静态 / 9临时文件 */
export const FILE_BIZ_MAP: Record<number, string> = {
  1: '题库源文件',
  2: '题目图片',
  3: '学习资料',
  4: '课程音视频',
  5: '头像',
  6: '公开静态',
  9: '临时文件',
}

/** 文件状态：1待上传 / 2已上传 / 3解析中 / 4已归档 / 5失败 */
export const FILE_STATUS_MAP: Record<number, { label: string; type: '' | 'success' | 'info' | 'warning' | 'danger' }> = {
  1: { label: '待上传', type: 'info' },
  2: { label: '已上传', type: 'success' },
  3: { label: '解析中', type: 'warning' },
  4: { label: '已归档', type: 'success' },
  5: { label: '失败', type: 'danger' },
}

// ---- 错题枚举 ----
/** 错题状态：1在错题本 / 2已移除 / 3已掌握 */
export const WRONG_STATUS_MAP: Record<number, { label: string; type: '' | 'success' | 'info' | 'warning' }> = {
  1: { label: '在错题本', type: 'warning' },
  2: { label: '已移除', type: 'info' },
  3: { label: '已掌握', type: 'success' },
}

// ---- 考试记录枚举 ----
/** 考试状态：1进行中 / 2已交卷 / 3超时自动交卷 / 4已作废 */
export const EXAM_STATUS_MAP: Record<number, { label: string; type: '' | 'success' | 'info' | 'warning' | 'danger' }> = {
  1: { label: '进行中', type: 'warning' },
  2: { label: '已交卷', type: 'success' },
  3: { label: '超时交卷', type: 'info' },
  4: { label: '已作废', type: 'danger' },
}

// ---- 订单枚举 ----
/** 订单类型：1会员 / 2题库购买 / 3学习资料购买 */
export const ORDER_TYPE_MAP: Record<number, string> = {
  1: '会员',
  2: '题库购买',
  3: '学习资料购买',
}

/** 订单状态：0待支付 / 1已支付 / 2已取消 / 3已退款 / 4已关闭 */
export const ORDER_STATUS_MAP: Record<number, { label: string; type: '' | 'success' | 'info' | 'warning' | 'danger' }> = {
  0: { label: '待支付', type: 'warning' },
  1: { label: '已支付', type: 'success' },
  2: { label: '已取消', type: 'info' },
  3: { label: '已退款', type: 'danger' },
  4: { label: '已关闭', type: 'info' },
}

/** 支付渠道：1微信支付 / 2支付宝 */
export const PAY_CHANNEL_MAP: Record<number, string> = {
  1: '微信支付',
  2: '支付宝',
}

// ---- 会员枚举 ----
/** 会员等级：0普通 / 1月卡 / 2季卡 / 3年卡 / 4永久 */
export const MEMBER_LEVEL_MAP: Record<number, string> = {
  0: '普通用户',
  1: '月卡',
  2: '季卡',
  3: '年卡',
  4: '永久会员',
}

/** 会员状态：1生效 / 2已过期 / 3已冻结 */
export const MEMBER_STATUS_MAP: Record<number, { label: string; type: '' | 'success' | 'info' | 'warning' | 'danger' }> = {
  1: { label: '生效', type: 'success' },
  2: { label: '已过期', type: 'info' },
  3: { label: '已冻结', type: 'danger' },
}

// ---- 用户枚举 ----
/** 用户状态：1正常 / 2禁用 / 3注销中 / 4已注销 */
export const USER_STATUS_MAP: Record<number, { label: string; type: '' | 'success' | 'danger' | 'warning' | 'info' }> = {
  1: { label: '正常', type: 'success' },
  2: { label: '禁用', type: 'danger' },
  3: { label: '注销中', type: 'warning' },
  4: { label: '已注销', type: 'info' },
}

/** 性别：1男 / 2女 / 0未知 */
export const GENDER_MAP: Record<number, string> = {
  0: '保密',
  1: '男',
  2: '女',
}
