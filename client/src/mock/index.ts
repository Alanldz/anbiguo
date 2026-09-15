/**
 * 开发期 Mock 数据
 * 说明：后端（第三步）未就绪前，api 层在 USE_MOCK = true 时读取此处数据，
 *       后端联调时把 src/api/index.ts 的 USE_MOCK 改为 false 即可切换，业务代码零改动。
 *       后端接口就绪后本文件整体删除。
 */

import type {
  BankCategory,
  ImportTask,
  Question,
  QuestionBank,
  StudySummary,
  UserProfile
} from '@/types'
import { BankSourceType, BankStatus, QuestionType } from '@/types'

export const mockUserProfile: UserProfile = {
  id: 2077,
  nickname: '135****6162_02178',
  avatar: '',
  mobile: '13500006162',
  uid: '732321247464',
  is_creator: true,
  member_level: 1,
  member_expired_at: '2026-12-31 23:59:59'
}

export const mockStudySummary: StudySummary = {
  practice_count: 128,
  accuracy: 82.4,
  wrong_count: 46,
  favorite_count: 18,
  study_minutes: 1560
}

export const mockCategories: BankCategory[] = [
  { id: 0, name: '热门推荐', code: 'hot' },
  { id: 1, name: '建筑工程', code: 'construction' },
  { id: 2, name: '财会金融', code: 'finance' },
  { id: 3, name: '考公考编', code: 'civil_service' },
  { id: 4, name: '驾照考试', code: 'driving' },
  { id: 5, name: '职业资格', code: 'professional' },
  { id: 6, name: '特种作业', code: 'special_operation' },
  { id: 7, name: '消防救援', code: 'fire_rescue' },
  { id: 8, name: '医药卫生', code: 'medical' },
  { id: 9, name: '交通运输', code: 'transport' },
  { id: 10, name: '电力通信', code: 'power_telecom' },
  { id: 11, name: '技能鉴定', code: 'skill' },
  { id: 12, name: '生活服务', code: 'daily_service' }
]

export const mockBanks: QuestionBank[] = [
  {
    id: 1024,
    title: '英语-260117',
    category_id: 0,
    source_type: BankSourceType.Upload,
    question_count: 40,
    practiced_count: 10,
    status: BankStatus.Normal,
    created_at: '2026-01-15'
  },
  {
    id: 1025,
    title: '法理学 250111考试导入',
    category_id: 0,
    source_type: BankSourceType.Upload,
    question_count: 60,
    practiced_count: 24,
    status: BankStatus.Normal,
    created_at: '2025-01-06'
  },
  {
    id: 1026,
    title: '集大-毛概-1-11-AM9',
    category_id: 0,
    source_type: BankSourceType.Upload,
    question_count: 245,
    practiced_count: 60,
    status: BankStatus.Normal,
    created_at: '2024-12-31'
  },
  {
    id: 1027,
    title: '毛概2412',
    category_id: 0,
    source_type: BankSourceType.Upload,
    question_count: 252,
    practiced_count: 0,
    status: BankStatus.Normal,
    created_at: '2024-12-06'
  },
  {
    id: 1028,
    title: '2411新教材赠送模拟试卷2',
    category_id: 0,
    source_type: BankSourceType.Official,
    question_count: 300,
    practiced_count: 300,
    status: BankStatus.Normal,
    created_at: '2024-11-07'
  },
  {
    id: 1029,
    title: 'JC10心理咨询专业伦理单科作业题',
    category_id: 0,
    source_type: BankSourceType.Upload,
    question_count: 55,
    practiced_count: 12,
    status: BankStatus.Normal,
    created_at: '2024-11-01'
  }
]

export const mockQuestions: Question[] = [
  {
    id: 8801,
    bank_id: 1024,
    type: QuestionType.Single,
    title: 'Which of the following sentences is grammatically correct?',
    options: [
      { key: 'A', content: 'He suggested that she goes to the doctor at once.' },
      { key: 'B', content: 'He suggested that she go to the doctor at once.' },
      { key: 'C', content: 'He suggested that she going to the doctor at once.' },
      { key: 'D', content: 'He suggested that she gone to the doctor at once.' }
    ],
    answer: 'B',
    analysis:
      'suggest 表示"建议"时，其后 that 从句使用虚拟语气，谓语动词用 should + 动词原形，should 可省略，因此应为 she go。',
    is_favorited: false,
    note: ''
  },
  {
    id: 8802,
    bank_id: 1024,
    type: QuestionType.Multiple,
    title: '下列属于完全竞争市场特征的有？',
    options: [
      { key: 'A', content: '市场上有大量的买者和卖者' },
      { key: 'B', content: '产品具有同质性' },
      { key: 'C', content: '厂商可以自由进入或退出市场' },
      { key: 'D', content: '单个厂商能影响市场价格' }
    ],
    answer: 'ABC',
    analysis: '完全竞争市场中单个厂商是价格接受者，无法影响市场价格，故 D 项错误。',
    is_favorited: true,
    note: '记：买卖多、产品同、进出自由、信息完全。'
  },
  {
    id: 8803,
    bank_id: 1024,
    type: QuestionType.Judge,
    title: '企业在资产负债表中列示的"存货"项目，应当以成本与可变现净值孰低计量。',
    options: [
      { key: 'A', content: '正确' },
      { key: 'B', content: '错误' }
    ],
    answer: 'A',
    analysis: '《企业会计准则第 1 号——存货》规定，期末存货按成本与可变现净值孰低计量。',
    is_favorited: false,
    note: ''
  }
]

export const mockImportTasks: ImportTask[] = [
  {
    id: 501,
    bank_id: 1024,
    origin_name: '英语真题2026.xlsx',
    total_count: 120,
    parsed_count: 120,
    status: 'success'
  },
  {
    id: 502,
    bank_id: 1025,
    origin_name: '法理学考点整理.pdf',
    total_count: 80,
    parsed_count: 46,
    status: 'parsing'
  }
]
