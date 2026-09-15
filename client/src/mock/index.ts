/**
 * 开发期 Mock 数据
 * 说明：后端（第三步）未就绪前，api 层在 USE_MOCK = true 时读取此处数据，
 *       后端联调时把 src/api/index.ts 的 USE_MOCK 改为 false 即可切换，业务代码零改动。
 *       后端接口就绪后本文件整体删除。
 */

import type {
  BankCategory,
  FavoriteItem,
  ImportTask,
  NoteItem,
  PracticeRecordItem,
  PracticeStatus,
  Question,
  QuestionBank,
  QuestionOption,
  RecycleBankItem,
  StudySummary,
  UserProfile
} from '@/types'
import {
  BankSourceType,
  BankStatus,
  PracticeMode,
  PracticeStatus as PracticeStatusEnum,
  QuestionType
} from '@/types'

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

const FAV_OPTIONS_A: QuestionOption[] = [
  { key: 'A', content: '市场上有大量的买者和卖者' },
  { key: 'B', content: '产品具有同质性' },
  { key: 'C', content: '厂商可以自由进入或退出市场' },
  { key: 'D', content: '单个厂商能影响市场价格' }
]
const FAV_OPTIONS_B: QuestionOption[] = [
  { key: 'A', content: '成本与可变现净值孰低' },
  { key: 'B', content: '成本与重置成本孰低' },
  { key: 'C', content: '历史成本' },
  { key: 'D', content: '公允价值' }
]
const FAV_OPTIONS_C: QuestionOption[] = [
  { key: 'A', content: '业主方项目管理的目标包括投资目标、进度目标和质量目标' },
  { key: 'B', content: '设计方项目管理不涉及投资目标' },
  { key: 'C', content: '施工方对项目投资目标负全责' },
  { key: 'D', content: '供货方需管理项目整体进度' }
]

/** API-FAV-001 我的收藏列表 Mock */
export const mockFavorites: FavoriteItem[] = [
  {
    id: 9001,
    question_id: 8802,
    bank_id: 1024,
    bank_name: '英语-260117',
    question_type: QuestionType.Multiple,
    question_title: '下列属于完全竞争市场特征的有？',
    question_options: FAV_OPTIONS_A,
    question_difficulty: 2,
    folder_name: '经济学重点',
    created_at: '2026-02-10 09:21'
  },
  {
    id: 9002,
    question_id: 8803,
    bank_id: 1024,
    bank_name: '英语-260117',
    question_type: QuestionType.Judge,
    question_title: '存货应当按照成本与可变现净值孰低计量。',
    question_options: FAV_OPTIONS_B,
    question_difficulty: 1,
    folder_name: '会计基础',
    created_at: '2026-02-11 14:03'
  },
  {
    id: 9003,
    question_id: 7701,
    bank_id: 1026,
    bank_name: '集大-毛概-1-11-AM9',
    question_type: QuestionType.Single,
    question_title: '建设工程项目管理的核心任务是（）。',
    question_options: FAV_OPTIONS_C,
    question_difficulty: 3,
    folder_name: '建造师考点',
    created_at: '2026-02-12 20:45'
  },
  {
    id: 9004,
    question_id: 7702,
    bank_id: 1026,
    bank_name: '集大-毛概-1-11-AM9',
    question_type: QuestionType.Single,
    question_title: '下列关于投标报价编制原则的说法，正确的是（）。',
    question_options: [
      { key: 'A', content: '应完全依据企业定额编制' },
      { key: 'B', content: '不得低于成本报价' },
      { key: 'C', content: '可高于招标控制价' },
      { key: 'D', content: '无需考虑风险因素' }
    ],
    question_difficulty: 3,
    folder_name: '建造师考点',
    created_at: '2026-02-13 08:12'
  },
  {
    id: 9005,
    question_id: 6601,
    bank_id: 1025,
    bank_name: '法理学 250111考试导入',
    question_type: QuestionType.Essay,
    question_title: '简述法的规范作用的主要内容。',
    question_options: [],
    question_difficulty: 4,
    folder_name: '法理学背诵',
    created_at: '2026-02-14 21:30'
  },
  {
    id: 9006,
    question_id: 6602,
    bank_id: 1025,
    bank_name: '法理学 250111考试导入',
    question_type: QuestionType.Multiple,
    question_title: '下列属于我国非正式法律渊源的有？',
    question_options: [
      { key: 'A', content: '习惯' },
      { key: 'B', content: '判例' },
      { key: 'C', content: '政策' },
      { key: 'D', content: '制定法' }
    ],
    question_difficulty: 3,
    folder_name: '法理学背诵',
    created_at: '2026-02-15 10:08'
  },
  {
    id: 9007,
    question_id: 5501,
    bank_id: 1028,
    bank_name: '2411新教材赠送模拟试卷2',
    question_type: QuestionType.Judge,
    question_title: '建设工程施工许可证应当由施工单位申请领取。',
    question_options: [
      { key: 'A', content: '正确' },
      { key: 'B', content: '错误' }
    ],
    question_difficulty: 2,
    folder_name: '考前冲刺',
    created_at: '2026-02-16 19:55'
  },
  {
    id: 9008,
    question_id: 5502,
    bank_id: 1028,
    bank_name: '2411新教材赠送模拟试卷2',
    question_type: QuestionType.Blank,
    question_title: '混凝土标准养护龄期一般为______天。',
    question_options: [],
    question_difficulty: 1,
    folder_name: '考前冲刺',
    created_at: '2026-02-17 07:40'
  },
  {
    id: 9009,
    question_id: 4401,
    bank_id: 1029,
    bank_name: 'JC10心理咨询专业伦理单科作业题',
    question_type: QuestionType.Single,
    question_title: '心理咨询师在何种情形下可以突破保密原则？',
    question_options: [
      { key: 'A', content: '来访者同意公开时' },
      { key: 'B', content: '涉及自身职业声誉时' },
      { key: 'C', content: '来访者轻微违约时' },
      { key: 'D', content: '任何商业合作时' }
    ],
    question_difficulty: 4,
    folder_name: '心理咨询伦理',
    created_at: '2026-02-18 22:18'
  },
  {
    id: 9010,
    question_id: 4402,
    bank_id: 1029,
    bank_name: 'JC10心理咨询专业伦理单科作业题',
    question_type: QuestionType.Multiple,
    question_title: '心理咨询中知情同意应包含的内容有？',
    question_options: [
      { key: 'A', content: '咨询目标' },
      { key: 'B', content: '收费标准' },
      { key: 'C', content: '保密界限' },
      { key: 'D', content: '咨询师私人电话' }
    ],
    question_difficulty: 3,
    folder_name: '心理咨询伦理',
    created_at: '2026-02-19 13:02'
  }
]

/** API-NOTE-001 我的笔记列表 Mock */
export const mockNotes: NoteItem[] = [
  {
    id: 8001,
    question_id: 8802,
    bank_id: 1024,
    bank_name: '英语-260117',
    question_title: '下列属于完全竞争市场特征的有？',
    content: '记：买卖多、产品同、进出自由、信息完全。单个厂商是价格接受者，不能影响价格。',
    like_count: 12,
    created_at: '2026-02-10 09:25',
    updated_at: '2026-02-19 18:00'
  },
  {
    id: 8002,
    question_id: 8803,
    bank_id: 1024,
    bank_name: '英语-260117',
    question_title: '存货应当按照成本与可变现净值孰低计量。',
    content: '准则第1号规定，期末存货按成本与可变现净值孰低计量，跌价需计提存货跌价准备。',
    like_count: 5,
    created_at: '2026-02-11 14:10',
    updated_at: '2026-02-11 14:10'
  },
  {
    id: 8003,
    question_id: 7701,
    bank_id: 1026,
    bank_name: '集大-毛概-1-11-AM9',
    question_title: '建设工程项目管理的核心任务是（）。',
    content: '核心：目标控制（投资、进度、质量）。业主方是项目管理的核心。',
    like_count: 33,
    created_at: '2026-02-12 20:50',
    updated_at: '2026-02-18 09:20'
  },
  {
    id: 8004,
    question_id: 7702,
    bank_id: 1026,
    bank_name: '集大-毛概-1-11-AM9',
    question_title: '下列关于投标报价编制原则的说法，正确的是（）。',
    content: '不得低于成本报价（恶性竞争违法），可参考企业定额但不强制，应考虑风险。',
    like_count: 8,
    created_at: '2026-02-13 08:20',
    updated_at: '2026-02-13 08:20'
  },
  {
    id: 8005,
    question_id: 6601,
    bank_id: 1025,
    bank_name: '法理学 250111考试导入',
    question_title: '简述法的规范作用的主要内容。',
    content: '指引、评价、预测、教育、强制。记忆口诀：指评预教强。',
    like_count: 56,
    created_at: '2026-02-14 21:40',
    updated_at: '2026-02-19 20:30'
  },
  {
    id: 8006,
    question_id: 6602,
    bank_id: 1025,
    bank_name: '法理学 250111考试导入',
    question_title: '下列属于我国非正式法律渊源的有？',
    content: '习惯、判例、政策属非正式渊源；制定法是正式渊源。注意判例地位仍在发展中。',
    like_count: 21,
    created_at: '2026-02-15 10:15',
    updated_at: '2026-02-15 10:15'
  },
  {
    id: 8007,
    question_id: 5501,
    bank_id: 1028,
    bank_name: '2411新教材赠送模拟试卷2',
    question_title: '建设工程施工许可证应当由施工单位申请领取。',
    content: '错！施工许可证由建设单位（业主）申领，不是施工单位。',
    like_count: 3,
    created_at: '2026-02-16 20:00',
    updated_at: '2026-02-16 20:00'
  },
  {
    id: 8008,
    question_id: 5502,
    bank_id: 1028,
    bank_name: '2411新教材赠送模拟试卷2',
    question_title: '混凝土标准养护龄期一般为______天。',
    content: '标准养护 28 天，温度 20±2℃、湿度≥95%。',
    like_count: 17,
    created_at: '2026-02-17 07:50',
    updated_at: '2026-02-17 07:50'
  },
  {
    id: 8009,
    question_id: 4401,
    bank_id: 1029,
    bank_name: 'JC10心理咨询专业伦理单科作业题',
    question_title: '心理咨询师在何种情形下可以突破保密原则？',
    content: '仅当：来访者同意、或可能危及自身/他人生命安全、或法律要求时突破保密。',
    like_count: 9,
    created_at: '2026-02-18 22:25',
    updated_at: '2026-02-19 11:00'
  },
  {
    id: 8010,
    question_id: 4402,
    bank_id: 1029,
    bank_name: 'JC10心理咨询专业伦理单科作业题',
    question_title: '心理咨询中知情同意应包含的内容有？',
    content: '至少含目标、收费、保密界限与权利义务；私人电话不属于必要内容。',
    like_count: 4,
    created_at: '2026-02-19 13:10',
    updated_at: '2026-02-19 13:10'
  }
]

/** API-REC-001 练习记录列表 Mock */
export const mockPracticeRecords: PracticeRecordItem[] = [
  {
    id: 7001,
    bank_id: 1026,
    bank_name: '集大-毛概-1-11-AM9',
    practice_mode: PracticeMode.Sequence,
    total_count: 245,
    answered_count: 60,
    right_count: 48,
    wrong_count: 12,
    correct_rate: 80.0,
    duration_seconds: 3720,
    status: PracticeStatusEnum.Ongoing,
    started_at: '2026-02-12 20:00',
    finished_at: null
  },
  {
    id: 7002,
    bank_id: 1024,
    bank_name: '英语-260117',
    practice_mode: PracticeMode.Random,
    total_count: 40,
    answered_count: 40,
    right_count: 35,
    wrong_count: 5,
    correct_rate: 87.5,
    duration_seconds: 1500,
    status: PracticeStatusEnum.Finished,
    started_at: '2026-02-11 13:30',
    finished_at: '2026-02-11 13:55'
  },
  {
    id: 7003,
    bank_id: 1025,
    bank_name: '法理学 250111考试导入',
    practice_mode: PracticeMode.Wrong,
    total_count: 18,
    answered_count: 18,
    right_count: 16,
    wrong_count: 2,
    correct_rate: 88.9,
    duration_seconds: 960,
    status: PracticeStatusEnum.Finished,
    started_at: '2026-02-15 09:40',
    finished_at: '2026-02-15 09:56'
  },
  {
    id: 7004,
    bank_id: 1028,
    bank_name: '2411新教材赠送模拟试卷2',
    practice_mode: PracticeMode.Special,
    total_count: 300,
    answered_count: 300,
    right_count: 261,
    wrong_count: 39,
    correct_rate: 87.0,
    duration_seconds: 7200,
    status: PracticeStatusEnum.Finished,
    started_at: '2026-02-10 19:00',
    finished_at: '2026-02-10 21:00'
  },
  {
    id: 7005,
    bank_id: 1029,
    bank_name: 'JC10心理咨询专业伦理单科作业题',
    practice_mode: PracticeMode.Flashcard,
    total_count: 55,
    answered_count: 12,
    right_count: 11,
    wrong_count: 1,
    correct_rate: 91.7,
    duration_seconds: 420,
    status: PracticeStatusEnum.Ongoing,
    started_at: '2026-02-18 22:00',
    finished_at: null
  },
  {
    id: 7006,
    bank_id: 1024,
    bank_name: '英语-260117',
    practice_mode: PracticeMode.Behead,
    total_count: 30,
    answered_count: 30,
    right_count: 22,
    wrong_count: 8,
    correct_rate: 73.3,
    duration_seconds: 1100,
    status: PracticeStatusEnum.Abandoned,
    started_at: '2026-02-09 16:00',
    finished_at: '2026-02-09 16:18'
  },
  {
    id: 7007,
    bank_id: 1027,
    bank_name: '毛概2412',
    practice_mode: PracticeMode.Sequence,
    total_count: 252,
    answered_count: 252,
    right_count: 205,
    wrong_count: 47,
    correct_rate: 81.3,
    duration_seconds: 8100,
    status: PracticeStatusEnum.Finished,
    started_at: '2026-02-08 10:00',
    finished_at: '2026-02-08 12:15'
  },
  {
    id: 7008,
    bank_id: 1026,
    bank_name: '集大-毛概-1-11-AM9',
    practice_mode: PracticeMode.Random,
    total_count: 245,
    answered_count: 80,
    right_count: 63,
    wrong_count: 17,
    correct_rate: 78.8,
    duration_seconds: 2400,
    status: PracticeStatusEnum.Ongoing,
    started_at: '2026-02-19 14:00',
    finished_at: null
  }
]

/** API-BANK-008 回收站列表 Mock（结构同 QuestionBank + deleted_at） */
export const mockRecycleBanks: RecycleBankItem[] = [
  {
    id: 2001,
    title: '2023二建管理真题（已弃）',
    category_id: 1,
    source_type: BankSourceType.Upload,
    question_count: 120,
    practiced_count: 30,
    status: BankStatus.Normal,
    created_at: '2025-03-12',
    deleted_at: '2026-02-01 10:20'
  },
  {
    id: 2002,
    title: '考研英语高频词汇测试',
    category_id: 0,
    source_type: BankSourceType.Upload,
    question_count: 80,
    practiced_count: 12,
    status: BankStatus.Normal,
    created_at: '2025-05-20',
    deleted_at: '2026-02-03 15:42'
  },
  {
    id: 2003,
    title: '注安法规押题卷',
    category_id: 5,
    source_type: BankSourceType.Official,
    question_count: 150,
    practiced_count: 150,
    status: BankStatus.Normal,
    created_at: '2024-10-01',
    deleted_at: '2026-02-05 09:11'
  },
  {
    id: 2004,
    title: '教师资格证综合素质',
    category_id: 3,
    source_type: BankSourceType.Upload,
    question_count: 95,
    practiced_count: 0,
    status: BankStatus.Normal,
    created_at: '2025-01-18',
    deleted_at: '2026-02-07 21:03'
  },
  {
    id: 2005,
    title: '一级造价师计价',
    category_id: 1,
    source_type: BankSourceType.Purchased,
    question_count: 210,
    practiced_count: 64,
    status: BankStatus.Normal,
    created_at: '2024-08-09',
    deleted_at: '2026-02-09 13:28'
  },
  {
    id: 2006,
    title: '消防工程师技术实务',
    category_id: 7,
    source_type: BankSourceType.Upload,
    question_count: 320,
    practiced_count: 120,
    status: BankStatus.Normal,
    created_at: '2024-06-22',
    deleted_at: '2026-02-11 18:55'
  },
  {
    id: 2007,
    title: '公考行测言语理解',
    category_id: 3,
    source_type: BankSourceType.AiGenerated,
    question_count: 140,
    practiced_count: 40,
    status: BankStatus.Normal,
    created_at: '2025-07-30',
    deleted_at: '2026-02-13 11:36'
  },
  {
    id: 2008,
    title: 'CPA会计长期股权投资',
    category_id: 2,
    source_type: BankSourceType.Upload,
    question_count: 110,
    practiced_count: 88,
    status: BankStatus.Normal,
    created_at: '2025-02-14',
    deleted_at: '2026-02-15 08:47'
  },
  {
    id: 2009,
    title: '软考高项案例分析',
    category_id: 5,
    source_type: BankSourceType.Official,
    question_count: 75,
    practiced_count: 20,
    status: BankStatus.Normal,
    created_at: '2025-04-03',
    deleted_at: '2026-02-17 16:09'
  }
]
