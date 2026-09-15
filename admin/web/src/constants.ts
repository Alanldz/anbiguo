// 识途刷题 · 总管理后台 全局常量
// 集中管理状态映射等，禁止在页面散落魔法值

/** 统一的本地存储 key */
export const STORAGE_TOKEN_KEY = 'shitu_admin_token'

/** 登录失效错误码（跳登录页） */
export const CODE_UNAUTHORIZED = 10401
/** 无权限错误码 */
export const CODE_FORBIDDEN = 10403
/** 成功状态码 */
export const CODE_SUCCESS = 0

/** 默认分页大小 */
export const DEFAULT_PAGE_SIZE = 20

/** 菜单分组与路由元信息标识（permission 留空表示登录即可访问） */

/** 题库状态：1正常 / 2隐藏 / 3待审核 / 4已拒绝 */
export const BANK_STATUS_MAP: Record<number, { label: string; type: '' | 'success' | 'info' | 'warning' | 'danger' }> = {
  1: { label: '正常', type: 'success' },
  2: { label: '隐藏', type: 'info' },
  3: { label: '待审核', type: 'warning' },
  4: { label: '已拒绝', type: 'danger' },
}
/** 题库审核提交状态 */
export const BANK_AUDIT_PASS = 1
export const BANK_AUDIT_REJECT = 4
export const BANK_AUDIT_PENDING = 3
/** 题库上下架状态 */
export const BANK_STATUS_ONLINE = 1
export const BANK_STATUS_OFFLINE = 2

/** 用户状态：1正常 / 2禁用 */
export const USER_STATUS_MAP: Record<number, { label: string; type: '' | 'success' | 'danger' }> = {
  1: { label: '正常', type: 'success' },
  2: { label: '禁用', type: 'danger' },
}
export const USER_STATUS_NORMAL = 1
export const USER_STATUS_DISABLED = 2

/** 导入任务状态：1待解析 / 2解析中 / 3待校对 / 4已完成 / 5失败 */
export const IMPORT_TASK_STATUS_MAP: Record<number, { label: string; type: '' | 'success' | 'info' | 'warning' | 'danger' }> = {
  1: { label: '待解析', type: 'info' },
  2: { label: '解析中', type: 'warning' },
  3: { label: '待校对', type: 'warning' },
  4: { label: '已完成', type: 'success' },
  5: { label: '失败', type: 'danger' },
}

/** 管理员状态：1启用 / 2禁用 */
export const ADMIN_STATUS_MAP: Record<number, { label: string; type: '' | 'success' | 'danger' }> = {
  1: { label: '启用', type: 'success' },
  2: { label: '禁用', type: 'danger' },
}

/** 轮播状态：1启用 / 2停用 */
export const BANNER_STATUS_MAP: Record<number, { label: string; type: '' | 'success' | 'info' }> = {
  1: { label: '启用', type: 'success' },
  2: { label: '停用', type: 'info' },
}

/** 轮播位置（docs/03 §三 BannerPosition，字符串枚举） */
export const BANNER_POSITION_MAP: Record<string, string> = {
  home_top: '首页轮播',
  home_recommend: '首页推荐',
  mine_entry: '我的页入口',
}

/** 轮播跳转类型：1不跳转 / 2题库 / 3学习资料 / 4外链 / 5活动页 */
export const BANNER_LINK_TYPE_MAP: Record<number, string> = {
  1: '不跳转',
  2: '题库',
  3: '学习资料',
  4: '外链',
  5: '活动页',
}

/** 会员等级文案 */
export const MEMBER_LEVEL_MAP: Record<number, string> = {
  0: '普通用户',
  1: '月度会员',
  2: '年度会员',
  3: '终身会员',
}

/* ===================== P2 业务枚举映射 ===================== */

/** 分类状态：1正常 / 2隐藏 */
export const CATEGORY_STATUS_MAP: Record<number, { label: string; type: '' | 'success' | 'info' }> = {
  1: { label: '正常', type: 'success' },
  2: { label: '隐藏', type: 'info' },
}
export const CATEGORY_STATUS_NORMAL = 1
export const CATEGORY_STATUS_HIDDEN = 2

/** 订单类型：1会员 / 2题库购买 / 3资料购买 */
export const ORDER_TYPE_MAP: Record<number, string> = {
  1: '会员',
  2: '题库购买',
  3: '资料购买',
}

/** 订单状态：0待支付 / 1已支付 / 2已取消 / 3已退款 / 4已关闭 */
export const ORDER_STATUS_MAP: Record<number, { label: string; type: '' | 'success' | 'info' | 'warning' | 'danger' }> = {
  0: { label: '待支付', type: 'warning' },
  1: { label: '已支付', type: 'success' },
  2: { label: '已取消', type: 'info' },
  3: { label: '已退款', type: 'warning' },
  4: { label: '已关闭', type: 'info' },
}
/** 订单可退款状态（已支付） */
export const ORDER_STATUS_PAID = 1

/** 支付渠道：1微信支付 / 2支付宝 */
export const PAY_CHANNEL_MAP: Record<number, string> = {
  1: '微信支付',
  2: '支付宝',
}

/** 文件业务类型：1题库源文件 / 2题目图片 / 3学习资料 / 4课程音视频 / 5头像 / 6公开静态 / 9临时文件 */
export const BIZ_TYPE_MAP: Record<number, string> = {
  1: '题库源文件',
  2: '题目图片',
  3: '学习资料',
  4: '课程音视频',
  5: '头像',
  6: '公开静态',
  9: '临时文件',
}

/** 反馈类型：1功能异常 / 2体验建议 / 3其他 */
export const FEEDBACK_TYPE_MAP: Record<number, string> = {
  1: '功能异常',
  2: '体验建议',
  3: '其他',
}

/** 反馈状态：0待处理 / 1已处理 / 2已忽略 */
export const FEEDBACK_STATUS_MAP: Record<number, { label: string; type: '' | 'success' | 'info' | 'warning' }> = {
  0: { label: '待处理', type: 'warning' },
  1: { label: '已处理', type: 'success' },
  2: { label: '已忽略', type: 'info' },
}
/** 反馈处理结果 */
export const FEEDBACK_STATUS_HANDLED = 1
export const FEEDBACK_STATUS_IGNORED = 2

/** 会员套餐等级：1月卡 / 2季卡 / 3年卡 / 4永久 */
export const PLAN_LEVEL_MAP: Record<number, string> = {
  1: '月卡',
  2: '季卡',
  3: '年卡',
  4: '永久',
}
/** 会员套餐状态：1上架 / 2下架 */
export const PLAN_STATUS_MAP: Record<number, { label: string; type: '' | 'success' | 'info' }> = {
  1: { label: '上架', type: 'success' },
  2: { label: '下架', type: 'info' },
}
export const PLAN_STATUS_ONLINE = 1
export const PLAN_STATUS_OFFLINE = 2
