/**
 * 开发期 Mock 数据
 * 说明：后端（第三步）未就绪前，api 层在 USE_MOCK = true 时读取此处数据，
 *       后端联调时把 src/api/index.ts 的 USE_MOCK 改为 false 即可切换，业务代码零改动。
 *       后端接口就绪后本文件整体删除。
 */

import type {
  BankCategory,
  ErrorProneItem,
  ExamPaper,
  ExamRecordAnswer,
  ExamRecordDetail,
  FavoriteItem,
  ImportTask,
  MasteredItem,
  MemberPlan,
  MarketBankItem,
  NoteItem,
  NotificationItem,
  OrderItem,
  PracticeRecordItem,
  PracticeStatus,
  Question,
  QuestionBank,
  QuestionOption,
  RecycleBankItem,
  ResourceItem,
  SearchQuestionItem,
  StudySummary,
  UserProfile
} from '@/types'
import {
  BankSourceType,
  BankStatus,
  NotificationType,
  OrderStatus,
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

/**
 * API-IMP-002 导入任务 Mock
 * 覆盖后端五态：success / parsing / proofread / failed（另含 pending 语义同 parsing 前置）
 * 本期后端为同步占位：upload/manual/ocr 均返回 status=proofread（待校对）
 */
export const mockImportTasks: ImportTask[] = [
  {
    id: 501,
    bank_id: 1024,
    origin_name: '英语真题2026.xlsx',
    total_count: 120,
    parsed_count: 120,
    status: 'success',
    status_text: '解析完成',
    progress: 100
  },
  {
    id: 502,
    bank_id: 1025,
    origin_name: '法理学考点整理.pdf',
    total_count: 80,
    parsed_count: 46,
    status: 'parsing',
    status_text: '解析中',
    progress: 58
  },
  {
    id: 503,
    bank_id: 1026,
    origin_name: '毛概第一章练习.docx',
    total_count: 0,
    parsed_count: 0,
    status: 'proofread',
    status_text: '待校对',
    progress: 100
  },
  {
    id: 504,
    bank_id: 1027,
    origin_name: '行测题库扫描版.pdf',
    total_count: 0,
    parsed_count: 0,
    status: 'failed',
    status_text: '解析失败',
    progress: 0,
    fail_reason: '文档为扫描图片且无文字层，无法提取题目内容，请改用拍照录题'
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

/** API-MSG-001 消息通知列表 Mock（混合三种 type 与已读/未读） */
export const mockNotifications: NotificationItem[] = [
  {
    id: 6001,
    type: NotificationType.Business,
    title: '题库审核通过',
    content: '您上传的题库「法理学 250111考试导入」已通过审核，现已上架，快去分享给同学吧！',
    biz_type: 'bank',
    biz_id: 1025,
    is_read: 0,
    read_at: null,
    created_at: '2026-02-19 10:24'
  },
  {
    id: 6002,
    type: NotificationType.Business,
    title: '会员到期提醒',
    content: '您的 VIP 会员将于 2026-02-28 到期，续费可继续享受 AI 出题等 45+ 项权益。',
    biz_type: 'member',
    biz_id: 2077,
    is_read: 0,
    read_at: null,
    created_at: '2026-02-19 08:00'
  },
  {
    id: 6003,
    type: NotificationType.Business,
    title: '错题本周报',
    content: '本周您在「集大-毛概-1-11-AM9」错题减少 6 题，正确率提升至 80%，继续加油！',
    biz_type: 'report',
    biz_id: 1026,
    is_read: 0,
    read_at: null,
    created_at: '2026-02-18 21:30'
  },
  {
    id: 6004,
    type: NotificationType.System,
    title: '账号安全提醒',
    content: '您的账号于 02-18 19:42 在新设备上登录，如非本人操作请及时修改密码。',
    biz_type: 'account',
    biz_id: 2077,
    is_read: 0,
    read_at: null,
    created_at: '2026-02-18 19:42'
  },
  {
    id: 6005,
    type: NotificationType.System,
    title: '系统升级公告',
    content: '识途刷题将于 02-20 02:00~04:00 进行系统升级，期间可能出现短暂无法访问。',
    biz_type: 'system',
    biz_id: 0,
    is_read: 1,
    read_at: '2026-02-18 09:05',
    created_at: '2026-02-17 18:00'
  },
  {
    id: 6006,
    type: NotificationType.Interaction,
    title: '笔记收到点赞',
    content: '您的笔记「建设工程项目管理的核心任务」被 12 位同学点赞，快去看看吧。',
    biz_type: 'note',
    biz_id: 8003,
    is_read: 1,
    read_at: '2026-02-17 12:10',
    created_at: '2026-02-17 11:02'
  },
  {
    id: 6007,
    type: NotificationType.Business,
    title: '订单支付成功',
    content: '您购买的题库「一级造价师计价」已到账，可在「我的题库」中开始练习。',
    biz_type: 'order',
    biz_id: 50012,
    is_read: 1,
    read_at: '2026-02-16 15:20',
    created_at: '2026-02-16 15:18'
  },
  {
    id: 6008,
    type: NotificationType.Interaction,
    title: '题目解析被采纳',
    content: '您在「英语-260117」提交的解析建议已被采纳，奖励 50 积分。',
    biz_type: 'question',
    biz_id: 8801,
    is_read: 1,
    read_at: '2026-02-15 20:00',
    created_at: '2026-02-15 16:45'
  },
  {
    id: 6009,
    type: NotificationType.System,
    title: '版本更新提示',
    content: '新版本 v1.0.1 已发布：优化斩题体验，修复已知问题，欢迎更新。',
    biz_type: 'system',
    biz_id: 0,
    is_read: 1,
    read_at: '2026-02-14 09:30',
    created_at: '2026-02-14 09:00'
  },
  {
    id: 6010,
    type: NotificationType.Business,
    title: '题库审核未通过',
    content: '您上传的题库「英语测试卷」因选项缺失未通过审核，请修改后重新提交。',
    biz_type: 'bank',
    biz_id: 1030,
    is_read: 1,
    read_at: '2026-02-13 11:00',
    created_at: '2026-02-13 10:12'
  }
]

/** API-MST-001 我的斩题列表 Mock */
export const mockMastered: MasteredItem[] = [
  {
    id: 7101,
    question_id: 8801,
    bank_id: 1024,
    bank_name: '英语-260117',
    question_title: 'Which of the following sentences is grammatically correct?',
    question_type: QuestionType.Single,
    question_options: [],
    question_difficulty: 3,
    wrong_count: 4,
    right_streak: 3,
    mastered_at: '2026-02-18 15:30'
  },
  {
    id: 7102,
    question_id: 7701,
    bank_id: 1026,
    bank_name: '集大-毛概-1-11-AM9',
    question_title: '建设工程项目管理的核心任务是（）。',
    question_type: QuestionType.Single,
    question_options: [],
    question_difficulty: 2,
    wrong_count: 2,
    right_streak: 5,
    mastered_at: '2026-02-17 20:12'
  },
  {
    id: 7103,
    question_id: 5501,
    bank_id: 1028,
    bank_name: '2411新教材赠送模拟试卷2',
    question_title: '建设工程施工许可证应当由施工单位申请领取。',
    question_type: QuestionType.Judge,
    question_options: [],
    question_difficulty: 2,
    wrong_count: 5,
    right_streak: 4,
    mastered_at: '2026-02-16 19:40'
  },
  {
    id: 7104,
    question_id: 5502,
    bank_id: 1028,
    bank_name: '2411新教材赠送模拟试卷2',
    question_title: '混凝土标准养护龄期一般为______天。',
    question_type: QuestionType.Blank,
    question_options: [],
    question_difficulty: 1,
    wrong_count: 1,
    right_streak: 3,
    mastered_at: '2026-02-15 11:25'
  },
  {
    id: 7105,
    question_id: 6602,
    bank_id: 1025,
    bank_name: '法理学 250111考试导入',
    question_title: '下列属于我国非正式法律渊源的有？',
    question_type: QuestionType.Multiple,
    question_options: [],
    question_difficulty: 4,
    wrong_count: 6,
    right_streak: 4,
    mastered_at: '2026-02-14 22:05'
  },
  {
    id: 7106,
    question_id: 4401,
    bank_id: 1029,
    bank_name: 'JC10心理咨询专业伦理单科作业题',
    question_title: '心理咨询师在何种情形下可以突破保密原则？',
    question_type: QuestionType.Single,
    question_options: [],
    question_difficulty: 5,
    wrong_count: 3,
    right_streak: 5,
    mastered_at: '2026-02-13 09:48'
  },
  {
    id: 7107,
    question_id: 8803,
    bank_id: 1024,
    bank_name: '英语-260117',
    question_title: '企业在资产负债表中列示的"存货"项目，应当以成本与可变现净值孰低计量。',
    question_type: QuestionType.Judge,
    question_options: [],
    question_difficulty: 2,
    wrong_count: 2,
    right_streak: 3,
    mastered_at: '2026-02-12 17:33'
  },
  {
    id: 7108,
    question_id: 7702,
    bank_id: 1026,
    bank_name: '集大-毛概-1-11-AM9',
    question_title: '下列关于投标报价编制原则的说法，正确的是（）。',
    question_type: QuestionType.Single,
    question_options: [],
    question_difficulty: 3,
    wrong_count: 3,
    right_streak: 4,
    mastered_at: '2026-02-11 14:20'
  }
]

/** API-ERR-001 易错题集 Mock（correct_rate 15~45，answer_count 数百~数千） */
export const mockErrorProne: ErrorProneItem[] = [
  {
    id: 8101,
    bank_id: 1024,
    question_title: 'He suggested that she ______ to the doctor at once.',
    question_type: QuestionType.Single,
    question_options: [],
    question_difficulty: 3,
    correct_rate: 22.4,
    answer_count: 3268,
    is_wrong: true
  },
  {
    id: 8102,
    bank_id: 1024,
    question_title: '下列属于完全竞争市场特征的有？',
    question_type: QuestionType.Multiple,
    question_options: [],
    question_difficulty: 4,
    correct_rate: 18.7,
    answer_count: 2415,
    is_wrong: false
  },
  {
    id: 8103,
    bank_id: 1025,
    question_title: '下列关于法的基本特征的说法，错误的是（）。',
    question_type: QuestionType.Single,
    question_options: [],
    question_difficulty: 3,
    correct_rate: 31.2,
    answer_count: 1876,
    is_wrong: true
  },
  {
    id: 8104,
    bank_id: 1025,
    question_title: '简述法律原则与法律规则的区别。',
    question_type: QuestionType.Essay,
    question_options: [],
    question_difficulty: 5,
    correct_rate: 15.6,
    answer_count: 842,
    is_wrong: false
  },
  {
    id: 8105,
    bank_id: 1026,
    question_title: '建设项目工程总承包的主要意义在于（）。',
    question_type: QuestionType.Single,
    question_options: [],
    question_difficulty: 3,
    correct_rate: 28.9,
    answer_count: 4532,
    is_wrong: true
  },
  {
    id: 8106,
    bank_id: 1026,
    question_title: '施工成本管理的任务包括成本计划、成本控制、成本核算、成本分析和成本考核。',
    question_type: QuestionType.Judge,
    question_options: [],
    question_difficulty: 2,
    correct_rate: 44.3,
    answer_count: 5210,
    is_wrong: false
  },
  {
    id: 8107,
    bank_id: 1028,
    question_title: '大体积混凝土浇筑完成后，表面养护时间不少于______天。',
    question_type: QuestionType.Blank,
    question_options: [],
    question_difficulty: 3,
    correct_rate: 26.5,
    answer_count: 1963,
    is_wrong: true
  },
  {
    id: 8108,
    bank_id: 1029,
    question_title: '知情同意书中必须包含收费标准与保密界限的内容。',
    question_type: QuestionType.Judge,
    question_options: [],
    question_difficulty: 2,
    correct_rate: 39.8,
    answer_count: 1120,
    is_wrong: false
  }
]

// ============================================================
// 考试域 Mock（API-EXM-001 ~ 005）
// ============================================================

/** 试卷 9001 题目：一建建设工程法规（覆盖单选/多选/判断/填空/简答五种题型） */
const EXAM_PAPER_9001_QUESTIONS: Question[] = [
  {
    id: 9101,
    bank_id: 1028,
    type: QuestionType.Single,
    title: '根据《招标投标法》，中标通知书对招标人和中标人具有（）。',
    options: [
      { key: 'A', content: '合同效力' },
      { key: 'B', content: '法律效力' },
      { key: 'C', content: '要约效力' },
      { key: 'D', content: '参考效力' }
    ],
    answer: 'B',
    analysis: '《招标投标法》第 45 条规定，中标通知书发出后对招标人和中标人具有法律效力。中标通知书属于承诺，但合同效力以订立书面合同为准。',
    is_favorited: false,
    note: ''
  },
  {
    id: 9102,
    bank_id: 1028,
    type: QuestionType.Single,
    title: '建设工程施工许可证应当由（）申请领取。',
    options: [
      { key: 'A', content: '建设单位' },
      { key: 'B', content: '施工单位' },
      { key: 'C', content: '监理单位' },
      { key: 'D', content: '设计单位' }
    ],
    answer: 'A',
    analysis: '《建筑法》第 7 条规定，施工许可证由建设单位（业主方）按照国家有关规定向工程所在地县级以上人民政府建设行政主管部门申请领取。',
    is_favorited: false,
    note: ''
  },
  {
    id: 9103,
    bank_id: 1028,
    type: QuestionType.Multiple,
    title: '下列属于行政处罚种类的有（）。',
    options: [
      { key: 'A', content: '警告' },
      { key: 'B', content: '罚款' },
      { key: 'C', content: '拘役' },
      { key: 'D', content: '责令停产停业' }
    ],
    answer: 'ABD',
    analysis: '拘役属于刑罚中的主刑，不是行政处罚；《行政处罚法》规定的处罚种类包括警告、罚款、责令停产停业、暂扣或吊销许可证件、行政拘留等。',
    is_favorited: false,
    note: ''
  },
  {
    id: 9104,
    bank_id: 1028,
    type: QuestionType.Judge,
    title: '施工单位应当在施工现场入口处设置明显的安全警示标志。',
    options: [
      { key: 'A', content: '正确' },
      { key: 'B', content: '错误' }
    ],
    answer: 'A',
    analysis: '《建设工程安全生产管理条例》第 28 条规定，施工现场入口处、施工起重机械等危险部位应设置明显的安全警示标志。',
    is_favorited: false,
    note: ''
  },
  {
    id: 9105,
    bank_id: 1028,
    type: QuestionType.Judge,
    title: '建设工程质量保证金的比例不得超过工程价款结算总额的 5%。',
    options: [
      { key: 'A', content: '正确' },
      { key: 'B', content: '错误' }
    ],
    answer: 'B',
    analysis: '根据现行《建设工程质量保证金管理办法》，保证金总预留比例不得超过工程价款结算总额的 3%，题干中的 5% 为旧规。',
    is_favorited: false,
    note: ''
  },
  {
    id: 9106,
    bank_id: 1028,
    type: QuestionType.Blank,
    title: '混凝土标准养护的龄期一般为______天。',
    options: [],
    answer: '28',
    analysis: '标准养护条件为温度 20±2℃、相对湿度 95% 以上，养护龄期 28 天，以 28 天抗压强度评定混凝土强度等级。',
    is_favorited: false,
    note: ''
  },
  {
    id: 9107,
    bank_id: 1028,
    type: QuestionType.Single,
    title: '投标保证金不得超过招标项目估算价的（）。',
    options: [
      { key: 'A', content: '2%' },
      { key: 'B', content: '5%' },
      { key: 'C', content: '10%' },
      { key: 'D', content: '20%' }
    ],
    answer: 'A',
    analysis: '《招标投标法实施条例》第 26 条规定，投标保证金不得超过招标项目估算价的 2%。',
    is_favorited: false,
    note: ''
  },
  {
    id: 9108,
    bank_id: 1028,
    type: QuestionType.Essay,
    title: '简述建设工程竣工验收应当具备的条件。',
    options: [],
    answer: '（1）完成工程设计和合同约定的各项内容；（2）有完整的技术档案和施工管理资料；（3）有主要建材、构配件和设备的进场试验报告；（4）有勘察、设计、施工、监理等单位分别签署的质量合格文件；（5）有施工单位签署的工程保修书。',
    analysis: '《建设工程质量管理条例》第 16 条列举了竣工验收应当具备的五项条件，答题时按条目作答并展开说明。',
    is_favorited: false,
    note: ''
  }
]

/** 试卷 9002 题目：公考公共基础知识 */
const EXAM_PAPER_9002_QUESTIONS: Question[] = [
  {
    id: 9201,
    bank_id: 1027,
    type: QuestionType.Single,
    title: '“锲而不舍，金石可镂”出自（）。',
    options: [
      { key: 'A', content: '《论语》' },
      { key: 'B', content: '《劝学》' },
      { key: 'C', content: '《孟子》' },
      { key: 'D', content: '《庄子》' }
    ],
    answer: 'B',
    analysis: '出自荀子《劝学》：「锲而舍之，朽木不折；锲而不舍，金石可镂」，强调学习贵在坚持。',
    is_favorited: false,
    note: ''
  },
  {
    id: 9202,
    bank_id: 1027,
    type: QuestionType.Single,
    title: '我国现行宪法最近一次修正是在（）年。',
    options: [
      { key: 'A', content: '2014' },
      { key: 'B', content: '2015' },
      { key: 'C', content: '2018' },
      { key: 'D', content: '2023' }
    ],
    answer: 'C',
    analysis: '现行《宪法》为 1982 年宪法，历经 1988、1993、1999、2004、2018 年五次修正，最近一次为 2018 年。',
    is_favorited: false,
    note: ''
  },
  {
    id: 9203,
    bank_id: 1027,
    type: QuestionType.Multiple,
    title: '下列属于宪法规定的公民基本义务的有（）。',
    options: [
      { key: 'A', content: '维护国家统一和民族团结' },
      { key: 'B', content: '依照法律纳税' },
      { key: 'C', content: '受教育的义务' },
      { key: 'D', content: '遵守公共秩序' }
    ],
    answer: 'ABCD',
    analysis: '受教育既是公民的基本权利也是基本义务；维护国家统一、依法纳税、遵守公共秩序均为宪法明文规定的基本义务。',
    is_favorited: false,
    note: ''
  },
  {
    id: 9204,
    bank_id: 1027,
    type: QuestionType.Judge,
    title: '凡具有中华人民共和国国籍的人都是中华人民共和国公民。',
    options: [
      { key: 'A', content: '正确' },
      { key: 'B', content: '错误' }
    ],
    answer: 'A',
    analysis: '《宪法》第 33 条规定，凡具有中华人民共和国国籍的人都是中华人民共和国公民，国籍是公民资格的唯一标准。',
    is_favorited: false,
    note: ''
  },
  {
    id: 9205,
    bank_id: 1027,
    type: QuestionType.Blank,
    title: '我国的根本政治制度是______。',
    options: [],
    answer: '人民代表大会制度',
    analysis: '人民代表大会制度是我国的根本政治制度；注意与基本政治制度（如政党制度、民族区域自治制度等）相区分。',
    is_favorited: false,
    note: ''
  },
  {
    id: 9206,
    bank_id: 1027,
    type: QuestionType.Essay,
    title: '简述公文文种“通知”的主要适用范围。',
    options: [],
    answer: '通知适用于发布、传达要求下级机关执行和有关单位周知或者执行的事项，以及批转、转发公文，任免人员等。',
    analysis: '《党政机关公文处理工作条例》第 8 条对通知的适用范围作了规定，答题要点包括：发布传达事项、批转转发公文、人事任免。',
    is_favorited: false,
    note: ''
  }
]

/** 两套预置试卷（EXM-002 按 id 读取） */
export const mockExamPapers: ExamPaper[] = [
  {
    id: 9001,
    title: '一建法规 模拟考试',
    bank_id: 1028,
    duration_minutes: 60,
    total_score: 100,
    question_count: EXAM_PAPER_9001_QUESTIONS.length,
    questions: EXAM_PAPER_9001_QUESTIONS
  },
  {
    id: 9002,
    title: '公考公共基础 模拟卷',
    bank_id: 1027,
    duration_minutes: 45,
    total_score: 100,
    question_count: EXAM_PAPER_9002_QUESTIONS.length,
    questions: EXAM_PAPER_9002_QUESTIONS
  }
]

/** Mock 试卷仓库：EXM-001 组卷后写入，EXM-002 按 id 读取 */
export const mockPaperStore = new Map<number, ExamPaper>(
  mockExamPapers.map((paper) => [paper.id, paper])
)

/** API-EXM-001 组卷 Mock：从题库题池抽题（不足时循环补齐），并写入试卷仓库 */
export function createMockPaper(data: {
  bank_id: number
  bank_title: string
  question_count: number
  duration_minutes: number
}): ExamPaper {
  const pool = [...EXAM_PAPER_9001_QUESTIONS, ...EXAM_PAPER_9002_QUESTIONS]
  const questions: Question[] = Array.from({ length: data.question_count }, (_, i) => ({
    ...pool[i % pool.length],
    id: 880000 + i // 保证同卷内题目 id 唯一
  }))
  const paper: ExamPaper = {
    id: 9100 + mockPaperStore.size,
    title: `${data.bank_title} 模拟考试`,
    bank_id: data.bank_id,
    duration_minutes: data.duration_minutes,
    total_score: 100,
    question_count: questions.length,
    questions
  }
  mockPaperStore.set(paper.id, paper)
  return paper
}

/** 答案归一化：选择题按字母排序比对，文本答案去空格比对 */
function normalizeAnswer(value: string): string {
  return value.trim().toUpperCase().split('').sort().join('')
}

/** 生成错误的用户答案（预置记录用），保证与正确答案不一致 */
function pickWrongAnswer(question: Question): string {
  if (question.type === QuestionType.Blank || question.type === QuestionType.Essay) {
    return '（作答不完整）'
  }
  const wrong = question.options.find((option) => !question.answer.includes(option.key))
  return wrong?.key ?? ''
}

/** 当前时间文本（预置记录与交卷时间用） */
function nowText(): string {
  const d = new Date()
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
}

/**
 * 判分核心（EXM-003 Mock）：等分制逐题比对，score 保留 1 位小数
 */
function buildRecord(
  paper: ExamPaper,
  userAnswers: Array<{ question_id: number; answer: string }>,
  costSeconds: number,
  createdAt: string,
  id: number
): ExamRecordDetail {
  const questions = paper.questions ?? []
  const answerMap = new Map(userAnswers.map((item) => [item.question_id, item.answer]))
  const perScore = questions.length
    ? Math.round((paper.total_score / questions.length) * 10) / 10
    : 0
  let correctCount = 0
  const answers: ExamRecordAnswer[] = questions.map((question) => {
    const userAnswer = answerMap.get(question.id) ?? ''
    const isCorrect = userAnswer !== '' && normalizeAnswer(userAnswer) === normalizeAnswer(question.answer)
    if (isCorrect) correctCount += 1
    return {
      question_id: question.id,
      stem_preview: question.title,
      user_answer: userAnswer || '未作答',
      is_correct: isCorrect,
      score: isCorrect ? perScore : 0
    }
  })
  const score = Math.round(correctCount * perScore * 10) / 10
  return {
    id,
    paper_id: paper.id,
    title: paper.title,
    score,
    total_score: paper.total_score,
    correct_count: correctCount,
    question_count: questions.length,
    cost_seconds: costSeconds,
    created_at: createdAt,
    pass_score: 60,
    is_passed: score >= 60,
    answers
  }
}

/** Mock 考试记录仓库（按时间倒序，最新在前）；EXM-003 交卷后写入，EXM-004/005 读取 */
export const mockExamRecords: ExamRecordDetail[] = []

/** 预置考试记录描述：wrongIds 内的题目按答错/未答处理 */
const SEEDED_RECORDS: Array<{
  id: number
  paperId: number
  wrongIds: number[]
  costSeconds: number
  createdAt: string
}> = [
  { id: 3001, paperId: 9001, wrongIds: [9108], costSeconds: 1830, createdAt: '2026-09-15 11:30:00' },
  { id: 3002, paperId: 9002, wrongIds: [9206], costSeconds: 1500, createdAt: '2026-09-12 20:11:00' },
  { id: 3003, paperId: 9001, wrongIds: [9103, 9105, 9106, 9108], costSeconds: 2400, createdAt: '2026-09-10 09:05:00' },
  { id: 3004, paperId: 9002, wrongIds: [9203, 9205], costSeconds: 1200, createdAt: '2026-09-08 15:40:00' },
  { id: 3005, paperId: 9001, wrongIds: [9105, 9106], costSeconds: 2010, createdAt: '2026-09-05 19:22:00' },
  { id: 3006, paperId: 9002, wrongIds: [9202, 9203, 9205, 9206], costSeconds: 900, createdAt: '2026-09-02 10:10:00' }
]

// 按时间倒序写入：SEEDED_RECORDS 已按时间倒序排列
for (const seed of SEEDED_RECORDS) {
  const paper = mockPaperStore.get(seed.paperId)
  if (!paper) continue
  const questions = paper.questions ?? []
  const userAnswers = questions.map((question) => ({
    question_id: question.id,
    answer: seed.wrongIds.includes(question.id) ? pickWrongAnswer(question) : question.answer
  }))
  mockExamRecords.push(buildRecord(paper, userAnswers, seed.costSeconds, seed.createdAt, seed.id))
}

/** API-EXM-003 交卷 Mock：判分并写入记录仓库（幂等由后端保证，Mock 每次生成新记录） */
export function gradeMockExam(
  paper: ExamPaper,
  userAnswers: Array<{ question_id: number; answer: string }>,
  costSeconds: number
): ExamRecordDetail {
  const record = buildRecord(paper, userAnswers, costSeconds, nowText(), 3100 + mockExamRecords.length)
  mockExamRecords.unshift(record)
  return record
}

// ============================================================
// 题库市场 / 会员 / 订单 / 学习资料 / 搜索 Mock
// ============================================================

/** API-BANK-007 题库市场列表 Mock（8 个：考研/考公/一建/教资等） */
export const mockMarketBanks: MarketBankItem[] = [
  {
    id: 3001,
    title: '2026 考研英语（一）真题精讲',
    category_id: 0,
    source_type: BankSourceType.Official,
    question_count: 860,
    practiced_count: 15230,
    status: BankStatus.Normal,
    created_at: '2026-08-12',
    description: '覆盖 2010-2025 年考研英语一真题，逐题精讲，附高频词汇与长难句解析。',
    usage_count: 15230,
    is_recommend: true
  },
  {
    id: 3002,
    title: '2026 考研政治核心考点 1200 题',
    category_id: 0,
    source_type: BankSourceType.Official,
    question_count: 1200,
    practiced_count: 9860,
    status: BankStatus.Normal,
    created_at: '2026-08-20',
    description: '马原、毛中特、史纲、思修法基五大模块，浓缩核心考点，适合强化冲刺阶段。',
    usage_count: 9860,
    is_recommend: true
  },
  {
    id: 3003,
    title: '公务员行测专项训练（言语理解与表达）',
    category_id: 3,
    source_type: BankSourceType.Official,
    question_count: 640,
    practiced_count: 7342,
    status: BankStatus.Normal,
    created_at: '2026-07-15',
    description: '国考/省考行测言语模块专项，逻辑填空 + 片段阅读，真题占比 80%。',
    usage_count: 7342
  },
  {
    id: 3004,
    title: '公务员申论热点范文与真题解析',
    category_id: 3,
    source_type: BankSourceType.Official,
    question_count: 180,
    practiced_count: 4218,
    status: BankStatus.Normal,
    created_at: '2026-07-02',
    description: '近五年国考申论真题 + 36 篇热点范文，附标准答题结构拆解。',
    usage_count: 4218
  },
  {
    id: 3005,
    title: '一建建设工程法规高频题库',
    category_id: 1,
    source_type: BankSourceType.Official,
    question_count: 1520,
    practiced_count: 12680,
    status: BankStatus.Normal,
    created_at: '2026-06-18',
    description: '依据 2026 版新教材编写，覆盖法规全部章节，含近三年真题与押题卷。',
    usage_count: 12680,
    is_recommend: true
  },
  {
    id: 3006,
    title: '一级建造师建设工程经济章节练习',
    category_id: 1,
    source_type: BankSourceType.Official,
    question_count: 980,
    practiced_count: 5390,
    status: BankStatus.Normal,
    created_at: '2026-06-25',
    description: '工程经济、工程财务、建设工程估价三章分节练习，公式运用题精讲。',
    usage_count: 5390
  },
  {
    id: 3007,
    title: '教师资格证《综合素质》中小幼通用',
    category_id: 5,
    source_type: BankSourceType.Official,
    question_count: 720,
    practiced_count: 8930,
    status: BankStatus.Normal,
    created_at: '2026-05-30',
    description: '职业理念、法律法规、职业道德、文化素养、基本能力五模块全收录。',
    usage_count: 8930
  },
  {
    id: 3008,
    title: '注册会计师《会计》高频考点 1500 题',
    category_id: 2,
    source_type: BankSourceType.Official,
    question_count: 1500,
    practiced_count: 6120,
    status: BankStatus.Normal,
    created_at: '2026-05-12',
    description: '长期股权投资、合并报表、金融工具等重难点专题突破，附分录模板。',
    usage_count: 6120
  }
]

/** API-MBR-001 会员套餐列表 Mock（月/季/年/永久 4 档） */
export const mockMemberPlans: MemberPlan[] = [
  {
    id: 1,
    name: '月卡会员',
    level: 1,
    level_text: '月卡',
    duration_days: 30,
    price_amount: 30.0,
    origin_amount: 39.0,
    description: '适合短期冲刺备考',
    benefits: ['每日 AI 导题 10 次', '全题库免费刷', '精简题模式', '错题导出'],
    ai_import_quota: 300,
    is_recommend: false
  },
  {
    id: 2,
    name: '季卡会员',
    level: 2,
    level_text: '季卡',
    duration_days: 90,
    price_amount: 68.0,
    origin_amount: 117.0,
    description: '一个备考周期刚刚好',
    benefits: ['每日 AI 导题 20 次', '全题库免费刷', '精简题模式', '试题闪卡', '考点速记'],
    ai_import_quota: 1800,
    is_recommend: false
  },
  {
    id: 3,
    name: '年卡会员',
    level: 3,
    level_text: '年卡',
    duration_days: 365,
    price_amount: 199.0,
    origin_amount: 468.0,
    description: '全程备考首选，性价比最高',
    benefits: [
      '每日 AI 导题不限次',
      '全题库免费刷',
      '精简题模式',
      '试题闪卡',
      '考点速记',
      'AI 出题',
      '专属客服通道'
    ],
    ai_import_quota: 9999,
    is_recommend: true
  },
  {
    id: 4,
    name: '永久会员',
    level: 4,
    level_text: '永久',
    duration_days: 0,
    price_amount: 399.0,
    origin_amount: 999.0,
    description: '一次开通，终身有效',
    benefits: [
      '每日 AI 导题不限次',
      '全题库免费刷',
      '全部 AI 能力开放',
      '新功能优先体验',
      '专属客服通道'
    ],
    ai_import_quota: 9999,
    is_recommend: false
  }
]

/** API-ORD-001 我的订单列表 Mock（6 条覆盖各状态） */
export const mockOrders: OrderItem[] = [
  {
    id: 5001,
    order_no: 'OD20260915110000abcdef',
    order_type: 1,
    order_type_text: '会员',
    biz_id: 3,
    biz_title: '年卡会员',
    origin_amount: 468.0,
    discount_amount: 269.0,
    pay_amount: 199.0,
    status: OrderStatus.Unpaid,
    status_text: '待支付',
    created_at: '2026-09-15 11:00:00',
    paid_at: null
  },
  {
    id: 5002,
    order_no: 'OD20260912153000bcdef1',
    order_type: 1,
    order_type_text: '会员',
    biz_id: 2,
    biz_title: '季卡会员',
    origin_amount: 117.0,
    discount_amount: 49.0,
    pay_amount: 68.0,
    status: OrderStatus.Paid,
    status_text: '已支付',
    created_at: '2026-09-12 15:30:00',
    paid_at: '2026-09-12 15:31:22'
  },
  {
    id: 5003,
    order_no: 'OD20260905092000cdef23',
    order_type: 1,
    order_type_text: '会员',
    biz_id: 1,
    biz_title: '月卡会员',
    origin_amount: 39.0,
    discount_amount: 9.0,
    pay_amount: 30.0,
    status: OrderStatus.Paid,
    status_text: '已支付',
    created_at: '2026-09-05 09:20:00',
    paid_at: '2026-09-05 09:20:45'
  },
  {
    id: 5004,
    order_no: 'OD20260828184500def345',
    order_type: 1,
    order_type_text: '会员',
    biz_id: 4,
    biz_title: '永久会员',
    origin_amount: 999.0,
    discount_amount: 0.0,
    pay_amount: 999.0,
    status: OrderStatus.Refunded,
    status_text: '已退款',
    created_at: '2026-08-28 18:45:00',
    paid_at: '2026-08-28 18:46:10'
  },
  {
    id: 5005,
    order_no: 'OD20260820143000ef4567',
    order_type: 1,
    order_type_text: '会员',
    biz_id: 1,
    biz_title: '月卡会员',
    origin_amount: 39.0,
    discount_amount: 0.0,
    pay_amount: 39.0,
    status: OrderStatus.Canceled,
    status_text: '已取消',
    created_at: '2026-08-20 14:30:00',
    paid_at: null
  },
  {
    id: 5006,
    order_no: 'OD20260810101500f56789',
    order_type: 1,
    order_type_text: '会员',
    biz_id: 2,
    biz_title: '季卡会员',
    origin_amount: 117.0,
    discount_amount: 49.0,
    pay_amount: 68.0,
    status: OrderStatus.Paid,
    status_text: '已支付',
    created_at: '2026-08-10 10:15:00',
    paid_at: '2026-08-10 10:15:38'
  }
]

/** API-FIL-003 学习资料列表 Mock（8 条，覆盖常见类型） */
export const mockResources: ResourceItem[] = [
  {
    id: 7101,
    bank_id: 1024,
    file_name: '英语语法核心讲义.pdf',
    file_type: 'pdf',
    file_size: 2048000,
    created_at: '2026-09-15 11:40:02'
  },
  {
    id: 7102,
    bank_id: 1024,
    file_name: '考研高频词汇表.xlsx',
    file_type: 'xlsx',
    file_size: 356000,
    created_at: '2026-09-14 09:12:40'
  },
  {
    id: 7103,
    bank_id: 1025,
    file_name: '法理学冲刺串讲.mp4',
    file_type: 'mp4',
    file_size: 268435456,
    created_at: '2026-09-13 20:05:11'
  },
  {
    id: 7104,
    bank_id: 1025,
    file_name: '法理学名词解释背诵版.docx',
    file_type: 'docx',
    file_size: 128500,
    created_at: '2026-09-12 15:30:00'
  },
  {
    id: 7105,
    bank_id: 1026,
    file_name: '一建法规历年真题汇总.pdf',
    file_type: 'pdf',
    file_size: 5242880,
    created_at: '2026-09-10 08:22:33'
  },
  {
    id: 7106,
    bank_id: 1026,
    file_name: '施工管理思维导图.png',
    file_type: 'png',
    file_size: 860000,
    created_at: '2026-09-08 19:44:20'
  },
  {
    id: 7107,
    bank_id: null,
    file_name: '错题整理方法论分享.mp3',
    file_type: 'mp3',
    file_size: 15360000,
    created_at: '2026-09-05 12:00:00'
  },
  {
    id: 7108,
    bank_id: null,
    file_name: '学习计划模板（通用）.xlsx',
    file_type: 'xlsx',
    file_size: 45800,
    created_at: '2026-09-01 10:18:52'
  }
]

/** API-SRC-001 题库内关键词搜索 Mock（题干摘要 / 题型 / 题库名） */
export const mockSearchQuestions: SearchQuestionItem[] = [
  {
    ...mockQuestions[0],
    bank_name: '英语-260117'
  },
  {
    ...mockQuestions[1],
    bank_name: '英语-260117'
  },
  {
    ...mockQuestions[2],
    bank_name: '英语-260117'
  },
  {
    id: 8804,
    bank_id: 1026,
    type: QuestionType.Single,
    title: '建设工程项目管理的核心任务是（）。',
    options: [
      { key: 'A', content: '项目目标控制' },
      { key: 'B', content: '合同管理' },
      { key: 'C', content: '信息管理' },
      { key: 'D', content: '组织协调' }
    ],
    answer: 'A',
    analysis: '项目管理的核心任务是项目的目标控制，业主方的项目管理是管理的核心。',
    is_favorited: false,
    note: '',
    bank_name: '集大-毛概-1-11-AM9'
  },
  {
    id: 8805,
    bank_id: 1028,
    type: QuestionType.Judge,
    title: '混凝土标准养护龄期一般为 28 天。',
    options: [
      { key: 'A', content: '正确' },
      { key: 'B', content: '错误' }
    ],
    answer: 'A',
    analysis: '标准养护条件为温度 20±2℃、相对湿度 95% 以上，养护龄期 28 天。',
    is_favorited: false,
    note: '',
    bank_name: '2411新教材赠送模拟试卷2'
  },
  {
    id: 8806,
    bank_id: 1025,
    type: QuestionType.Multiple,
    title: '下列属于我国正式法律渊源的有？',
    options: [
      { key: 'A', content: '宪法' },
      { key: 'B', content: '法律' },
      { key: 'C', content: '判例' },
      { key: 'D', content: '行政法规' }
    ],
    answer: 'ABD',
    analysis: '正式渊源包括宪法、法律、行政法规、地方性法规等；判例在我国属于非正式渊源。',
    is_favorited: false,
    note: '',
    bank_name: '法理学 250111考试导入'
  }
]
