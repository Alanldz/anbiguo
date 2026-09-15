# 客户端（小程序 / Android / H5）· 接口契约（P0 二十七接口）

> 本文件是 `client/src/`（前端）与 `server/app/Http/Controllers/Api/V1`（后端）的**唯一对接依据**。
> 任何字段增删必须先改本文件，再改两端代码。
> 相关规范：`docs/04-API接口规范与登记表.md` §二、§三（API-USER / API-IMP / API-QUE / API-WRG / API-EXM）。
> 已实现可参考：`console/API-CONTRACT.md`、`server/app/Services/Console/*`。

---

## 0. 全局约定

| 项 | 约定 |
| --- | --- |
| 基路径 | `/api/v1` |
| 鉴权守卫 | `auth:client`（`JWT_SECRET_CLIENT`，刷新机制同客户端） |
| 附带中间件 | `request.id`、`platform`（路由组 `client` 已挂） |
| 响应结构 | 统一 `App\Support\ApiResponse`：`{code,message,data,request_id,timestamp}`，`code=0` 为成功 |
| 分页参数 | 请求 `page`（默认 1）、`page_size`（默认 20，上限 100） |
| 分页响应 | `data = { list: [], pagination: { page, page_size, total, total_pages } }` |
| 时间格式 | `Y-m-d H:i:s` 字符串，空值返回 `null` |
| 数据归属 | 所有接口以当前登录用户为边界，越权抛 `BusinessException(ErrorCode::FORBIDDEN)` |
| 软删除 | 删除一律软删（`deleted_at`）；错题本「移除」用 `status=2 已移除`（领域软删，见 docs 既有约定） |
| Token 头 | `Authorization: Bearer {token}`、`X-Client-Platform: mp-weixin/app-android/h5` |

### 强制约束（docs/02 §五，与 console 一致）
1. 控制器禁止 SQL 与业务判断，只做「校验 → Service → ApiResponse」
2. 返回必须经 `App\Support\ApiResponse`，禁止手写 `response()->json()`
3. 可预期失败抛 `BusinessException(ErrorCode::XXX, ...)`
4. 状态值一律用 `App\Enums\` 枚举
5. 文件首行 `<?php` + `declare(strict_types=1);`
6. 所有查询以当前登录用户为边界
7. 中文注释，文件头带「台账：docs/04 §三 API-XXX-XXX」注释

### 枚举文本映射（复用 console/API-CONTRACT.md §枚举表）
QuestionType：1 单选 / 2 多选 / 3 判断 / 4 填空 / 5 简答
WrongQuestionStatus：1 在错题本 / 2 已移除 / 3 已掌握
ImportTaskStatus：1 待解析 / 2 解析中 / 3 待校对 / 4 已完成 / 5 失败
ExamStatus：1 进行中 / 2 已交卷 / 3 超时自动交卷 / 4 已作废
ImportMode：1 文档导入 / 2 手动录入 / 3 拍照OCR / 4 试题答案分离
QuestionReportType：1 答案错误 / 2 解析错误 / 3 题干错误 / 4 图片错误 / 5 题目重复 / 9 其他
MemberLevel：0 普通 / 1 月卡 / 2 季卡 / 3 年卡 / 4 永久

### 公共对象（与 client/src/types/index.ts 严格对齐）

```jsonc
// Question —— 题目（QUE-001 / WRG-001 / EXM 试卷题目均复用此形状）
{
  "id": 8801, "bank_id": 1024, "type": 1,
  "title": "题干文本（后端 question_items.stem）",
  "options": [ { "key": "A", "content": "选项内容" } ],
  "answer": "A", "analysis": "解析文本",
  "is_favorited": false, "note": ""
}

// UserProfile —— 个人资料（API-USER-001/002）
{
  "id": 2077, "nickname": "135****6162_02178", "avatar": "", "mobile": "13500006162",
  "uid": "732321247464", "is_creator": true, "member_level": 1,
  "member_expired_at": "2026-12-31 23:59:59"
}

// StudySummary —— 学习空间统计（API-USER-003）
{
  "practice_count": 128, "accuracy": 82.4, "wrong_count": 46,
  "favorite_count": 18, "study_minutes": 1560
}

// ImportTask —— 导入任务（API-IMP-002）
{
  "id": 502, "bank_id": 1025, "origin_name": "法理学考点整理.pdf",
  "total_count": 80, "parsed_count": 46,
  "status": "parsing",            // 见下方 status 取值说明
  "fail_reason": ""
}

// ExamPaper —— 试卷（API-EXM-001/002）
{
  "id": 9001, "title": "一建法规 模拟考试", "bank_id": 1024,
  "duration_minutes": 60, "total_score": 100, "question_count": 11,
  "questions": [ /* Question[] */ ]
}

// ExamRecord —— 考试记录（API-EXM-003/004/005）
{
  "id": 3001, "paper_id": 9001, "title": "一建法规 模拟考试",
  "score": 82, "total_score": 100, "correct_count": 9,
  "question_count": 11, "cost_seconds": 1830, "created_at": "2026-09-15 11:30:00"
}
```

> ImportTask.status 取值说明（**偏离前端 4 值联合类型，需前端把 `status` 放宽为 `string`**）：
> 后端 `ImportTaskStatus` 含「待校对(3)」五态，前端 `types/index.ts` 当前仅 `pending|parsing|success|failed`。
> 映射：1→`pending`、2→`parsing`、3→`proofread`、4→`success`、5→`failed`。本期占位任务固定 `status=proofread`。
> 其余字段 `total_count/parsed_count` 本期占位均为 0（真实解析待迭代）。

---

## 一、用户 · API-USER

### API-USER-001 `GET /user/profile`（需登录）
- resp：`UserProfile`

### API-USER-002 `PUT /user/profile`（需登录）
- req：`{ nickname?, avatar?, gender?, birthday?, province?, city?, exam_target?, bio? }`
- resp：`null`
- 仅更新 `user_profiles`；`is_creator` = 该用户是否拥有过题库（`question_banks.user_id = 当前用户` 存在即 true）

### API-USER-003 `GET /user/study-summary`（需登录）
- resp：`StudySummary`
- 字段口径（对齐前端形状，来源 `user_daily_stats` / 冗余表聚合）：
  - `practice_count` = 累计作答题数（Σ `user_daily_stats.answer_count`）
  - `accuracy` = 正确率（Σ right_count / Σ answer_count × 100，保留 1 位小数，无作答为 0）
  - `wrong_count` = 在错题本数（`user_wrong_questions.status=1` 计数）
  - `favorite_count` = 收藏题数（`user_favorite_questions` 计数）
  - `study_minutes` = 累计学习分钟（Σ `duration_seconds` ÷ 60 向下取整）

---

## 二、导入 · API-IMP（本期同步占位，AI 解析待迭代）

> 占位规则（docs/IMP）：upload/manual/ocr 校验入参与会员配额后创建 `question_import_tasks` 记录，
> `status=3 待校对`，`result_json={"pending":true}`，`progress=100`。真实 AI/文档/OCR 解析不在本期。

### API-IMP-001 `POST /import/upload`（需登录，配额）
- req：`{ file_id*, bank_id?, title?, split_answer? }`（`file_id` 来自 FIL-001/002 直传登记）
- resp：`{ id, task_no, bank_id, status, status_text }`
- 配额：非会员每日 AI/文档导题次数超限抛 `QUOTA_EXHAUSTED`（查 `user_members.ai_import_quota` + 当日任务计数）

### API-IMP-002 `GET /import/tasks/{id}`（需登录）
- resp：`ImportTask`

### API-IMP-003 `GET /import/template`（需登录）
- resp：`{ columns: [{ name, required, desc }], sample_url }`
- 固定模板结构（见下方 §六）

### API-IMP-004 `POST /import/manual`（需登录，配额）
- req：`{ bank_id*, type*, title*, options*: [{key,content}], answer*, analysis? }`
- resp：`{ id, task_no, bank_id, status, status_text }`
- **本期占位**：不直接落题目，创建 `import_mode=2 手动录入` 的导入任务（题目数据存 `result_json`），与 upload 一致返回任务 id。前端 `.id` 即任务 id。
- 越权：`bank_id` 非本人题库抛 `BANK_NO_PERMISSION`

### API-IMP-005 `POST /import/ocr`（需登录，配额）
- req：`{ file_id*, bank_id? }`
- resp：`{ id, task_no, text, bank_id, status, status_text }`（`text` 本期空串，OCR 待接入）
- 占位：创建 `import_mode=3 拍照OCR` 导入任务

---

## 三、题目与练习 · API-QUE

### API-QUE-001 `GET /question-banks/{id}/questions`（需登录）
- query：`page, page_size, mode(sequence|random|chapter), chapter_id?, question_type?`
- resp：分页 `Question[]`
  - `title` = `question_items.stem`；`options` 由 `question_options` 映射 `option_key→key`
  - `is_favorited` / `note` 按当前用户与题目关系实时计算
- 仅返回用户可访问题库（本人题库 / 官方 / 状态正常）下的 `status=1` 题目

### API-QUE-002 `POST /questions/{id}/answer`（需登录）
- req：`{ answer*, cost_seconds? }`（`question_id` 取路径参数）
- resp：`{ correct: boolean, answer: string, analysis: string }`
- 判分：按题型归一化比对（`answer` 为正确答案原文）
- 副作用（事务）：① 写 `user_practice_records`（单题练习，status=2 已完成）② 维护 `user_daily_stats` 当日计数 ③ 答错 upsert `user_wrong_questions`（同题重复错累计 `wrong_count`，不重复建行，status=1）④ 更新题目冗余 `answer_count/right_count/correct_rate`

### API-QUE-003 `POST /questions/{id}/favorite`（需登录）
- req：`{ favorite* }`（`true` 收藏 / `false` 取消）
- resp：`null`

### API-QUE-004 `PUT /questions/{id}/note`（需登录）
- req：`{ content* }`
- resp：`null`（upsert `user_question_notes`）

### API-QUE-005 `POST /questions/{id}/report`（需登录）
- req：`{ type?, reason?, images? }`（`type` 缺省 1 答案错误；`reason` 存 `content`；`images` 数组）
- resp：`null`（写 `question_reports`）

---

## 四、错题 · API-WRG

### API-WRG-001 `GET /wrong-questions`（需登录）
- query：`page, page_size, bank_id?, question_type?`（默认 `status=1 在错题本`）
- resp：分页 `Question[]`，每条额外带错题本字段：
```jsonc
{
  "id": 9,                  // 错题记录 id（用于 DELETE /wrong-questions/{id}）
  "question_id": 1001, "bank_id": 1, "type": 1,
  "title": "题干文本", "options": [...], "answer": "A", "analysis": "解析",
  "is_favorited": false, "note": "",
  "wrong_count": 3, "last_wrong_at": "2026-09-12 20:00:00",
  "status": 1, "status_text": "在错题本"
}
```
> 注：列表项 `id` 为**错题记录 id**（非题目 id），供 WRG-002 删除使用；题目 id 在 `question_id` 字段。

### API-WRG-002 `DELETE /wrong-questions/{id}`（需登录）
- resp：`null`
- 软删：置 `user_wrong_questions.status=2 已移除`（领域软删，既有约定）；越权抛 `FORBIDDEN`

---

## 五、考试 · API-EXM

### API-EXM-001 `POST /exam-papers`（需登录）
- req：`{ bank_id*, question_count*, duration_minutes*, types?[] }`
- resp：`ExamPaper`（含 `questions`）
- 组卷：从题库 `status=1` 题目随机抽 `question_count`（`types` 过滤题型），写入 `exam_papers` + `exam_paper_questions`；`total_score` = Σ 选题分值；`title` 默认「{题库标题} 模拟考试」

### API-EXM-002 `GET /exam-papers/{id}`（需登录）
- resp：`ExamPaper`（含 `questions`，按试卷内 `sort_order`）

### API-EXM-003 `POST /exam-records`（需登录，幂等）
- req：`{ paper_id*, answers*: [{question_id, answer}], cost_seconds? }`
- resp：`ExamRecord`
- **幂等**：同一用户同一 `paper_id` 已存在「已交卷/超时」记录则直接返回已有成绩，不重复计分
- 判分：逐题比对写 `exam_answers`；累计 `get_score/right_count/wrong_count/correct_rate`；`is_passed = get_score >= pass_score`；更新错题本（来源=考试）与当日统计

### API-EXM-004 `GET /exam-records/{id}`（需登录）
- resp：`ExamRecord` + `{ answers: [{question_id, stem_preview, user_answer, is_correct, score}] }`

### API-EXM-005 `GET /exam-records`（需登录）
- query：`page, page_size, bank_id?, keyword?`
- resp：分页 `ExamRecord[]`

---

## 六、导入模板固定结构（API-IMP-003）

```json
{
  "columns": [
    { "name": "题干", "required": true, "desc": "题目内容，支持富文本" },
    { "name": "题型", "required": true, "desc": "单选/多选/判断/填空/简答" },
    { "name": "选项", "required": false, "desc": "选择题填写，格式：A.选项内容|B.选项内容" },
    { "name": "答案", "required": true, "desc": "选择题填选项字母，判断题填 对/错" },
    { "name": "解析", "required": false, "desc": "答案解析" },
    { "name": "难度", "required": false, "desc": "易/中/难" },
    { "name": "章节", "required": false, "desc": "所属章节名称" }
  ],
  "sample_url": ""
}
```

---

## 七、会员 · API-MBR（本期仅下单，支付待微信支付接入）

### API-MBR-001 `GET /member/plans`（需登录）
- resp：`{ list: MemberPlan[] }`
- 读 `order_member_plans`（status=1 上架、未软删），按 `sort_order` 升序。
- 注：前端 `client/src/types/index.ts` 暂无 `MemberPlan` 类型，本契约先行定义，待前端补类型。

```jsonc
// MemberPlan —— 会员套餐（无对应前端类型，本次定义，待补）
{
  "id": 1, "name": "连续包月",
  "level": 1,                  // MemberLevel：0 普通 1 月卡 2 季卡 3 年卡 4 永久
  "level_text": "月卡",
  "duration_days": 30,         // 有效天数，永久为 0
  "price_amount": 30.00,       // 现价（元）
  "origin_amount": 39.00,      // 原价（划线价）
  "description": "",
  "benefits": ["每日 AI 导题 10 次", "全题库免费刷"],  // 由 benefits_json 解析为数组
  "ai_import_quota": 10,       // 赠送 AI 导题配额
  "is_recommend": true
}
```

### API-MBR-002 `POST /member/orders`（需登录）
- req：`{ plan_id* }`
- resp：`{ order_no, order_type, biz_id, biz_title, origin_amount, discount_amount, pay_amount, status, status_text, expired_at }`
- 校验套餐存在且 status=1 上架；否则抛 `DATA_NOT_FOUND`（不存在）/ `PLAN_OFF_SHELF`（已下架）。
- 创建 `order_orders`：`order_type=1 会员`、`status=0 待支付`、`expired_at=now+30min`。
- **支付方式待微信支付接入，本期仅返回待支付订单**，支付状态流转留待 API-PAY-001/002。
- `order_type`/`status` 取值见 `OrderType`/`OrderStatus` 枚举（`status_text` 由枚举 label 给出）。

---

## 八、订单 · API-ORD（需登录，仅本人订单）

### API-ORD-001 `GET /orders`（需登录）
- query：`page, page_size, status?`（status 可选：0 待支付 1 已支付 2 已取消 3 已退款 4 已关闭）
- resp：分页 `OrderItem[]`

```jsonc
// OrderItem —— 我的订单（无对应前端类型，本次定义，待补）
{
  "id": 1, "order_no": "OD20260915110000abcdef",
  "order_type": 1, "order_type_text": "会员",
  "biz_id": 2, "biz_title": "年卡会员",
  "origin_amount": 399.00, "discount_amount": 0.00, "pay_amount": 399.00,
  "status": 0, "status_text": "待支付",
  "created_at": "2026-09-15 11:00:00", "paid_at": null
}
```

---

## 九、搜索 · API-SRC

### API-SRC-001 `GET /search/questions`（需登录）
- query：`keyword*（题干模糊匹配，必填）`、`bank_id?（校验归属或可见性）`、`type?（题型筛选）`、`page, page_size`
- resp：分页 `Question[]`（复用 §三 API-QUE-001 的 Question 形状，含 `answer`/`analysis`；以契约为准）
- 校验：`keyword` 缺失抛 `PARAM_MISSING`；`bank_id` 非本人且非官方/非正常的不可见题库抛 `BANK_NO_PERMISSION`。
- 匹配：`question_items.stem LIKE %keyword%`（`%`/`_` 已转义），仅 `status=1` 且未软删；`bank_id`/`type` 可选过滤。
- 注：SRC-002（拍照/文字搜题，依赖 AI）不在本期；本契约不加 SRC-002。

---

## 十、P0 补漏接口（API-FAV-001 / NOTE-001 / REC-001 / USER-004 / BANK-008 ~ 009）

> 下列接口为「列表 / 注销 / 回收站」类补齐，字段名须逐字与客户端一致。
> 分页结构与全局约定一致：`data = { list: [], pagination: { page, page_size, total, total_pages } }`。

### API-FAV-001 `GET /favorites`（需登录）
- query：`page`(默认1)、`page_size`(默认10，≤50)、`bank_id?`、`keyword?`(题干模糊，已转义 %/_)
- 数据源：`user_favorite_questions`(软删过滤) JOIN `question_items` JOIN `bank_question_banks` JOIN `question_options`
- resp：`{ list: FavoriteItem[], pagination:{page,page_size,total,total_pages} }`
- `FavoriteItem`：
```jsonc
{
  "id": 12, "question_id": 8801, "bank_id": 1024, "bank_name": "一建法规",
  "question_type": 1, "question_title": "题干文本",
  "question_options": [ { "key": "A", "content": "选项" } ],
  "question_difficulty": 2, "folder_name": "默认收藏夹",
  "created_at": "2026-09-15 10:00:00"
}
```
- 枚举 `question_type`：1 单选 / 2 多选 / 3 判断 / 4 填空 / 5 简答（数值输出，文本映射前端做）

### API-NOTE-001 `GET /notes`（需登录）
- query：`page`、`page_size`(默认10，≤50)、`bank_id?`、`keyword?`(content 模糊，已转义)
- 数据源：`user_question_notes` JOIN `question_items` JOIN `bank_question_banks`
- resp：`{ list: NoteItem[], pagination }`
- `NoteItem`：
```jsonc
{
  "id": 7, "question_id": 8801, "bank_id": 1024, "bank_name": "一建法规",
  "question_title": "题干文本", "content": "笔记内容", "like_count": 3,
  "created_at": "2026-09-15 10:00:00", "updated_at": "2026-09-15 11:00:00"
}
```

### API-REC-001 `GET /practice-records`（需登录）
- query：`page`、`page_size`、`bank_id?`、`status?`(1=进行中 2=已完成 3=已放弃)
- 数据源：`user_practice_records` LEFT JOIN `bank_question_banks`（bank_id=0 时 bank_name 为空串）
- resp：`{ list: PracticeRecord[], pagination }`
- `PracticeRecord`：
```jsonc
{
  "id": 33, "bank_id": 1024, "bank_name": "一建法规",
  "practice_mode": 1, "total_count": 50, "answered_count": 50,
  "right_count": 41, "wrong_count": 9, "correct_rate": 82.00,
  "duration_seconds": 1830, "status": 2,
  "started_at": "2026-09-15 10:00:00", "finished_at": "2026-09-15 10:30:00"
}
```
- 枚举 `practice_mode`：1 顺序 / 2 随机 / 3 专项 / 4 错题重做 / 5 闪卡 / 6 斩题
- 枚举 `status`：1 进行中 / 2 已完成 / 3 已放弃（数值输出）
- 时间 `Y-m-d H:i:s` 字符串，`null` 输出 `null`；`correct_rate` 为百分比数值（如 82.00）

### API-USER-004 `POST /user/cancel`（需登录）
- req：`{ confirm*: true（必须字面 true）, reason?: string(≤200) }`
- 逻辑：confirm 非字面 true 抛 `PARAM_INVALID`；置 `user_accounts.status=3 注销中`；吊销当前 Token（复用 logout 黑名单逻辑）。
  - **注销后立即不可登录**：`EnsureUserActiveMiddleware` 拦截 CANCELING/CANCELED 状态，全部既有 Token 随即失效。
  - `reason` 本期不落库（`user_accounts` 无该字段），后续如需审计再加字段。
  - 30 天后物理清除由后续迭代定时任务处理（本期不做）。
- resp：`{ canceled: true, message: "账号已注销" }`

### API-BANK-008 `GET /question-banks/recycle`（需登录）
- query：`page`、`page_size`、`keyword?`(名称模糊)
- 数据源：`bank_question_banks` **onlyTrashed** 且 `user_id=本人`
- resp：`{ list: RecycledBank[], pagination }`（`RecycledBank` 同「我的题库列表」项结构 + `deleted_at`）
- `RecycledBank`：复用 `QuestionBankResource` 全部字段，并额外返回：
  - `deleted_at`：`Y-m-d H:i:s` 字符串（软删时间）

### API-BANK-009 `PUT /api/v1/question-banks/{id}/restore`（需登录）
- 仅恢复本人软删题库：找不到或非本人软删记录抛 `DATA_NOT_FOUND`
- `restore()` 后回加分类题库计数（与删除时对称 -1/+1）；题库冗余 `question_count` 软删不动，无需重算
- resp：`{ restored: true, ...QuestionBank }`（返回结构与题库详情 `QuestionBankResource` 一致）

---

## 十一、P1 补齐接口（API-MSG-001 ~ 004 / MST-001 ~ 002 / ERR-001）

> 消息通知中心、斩题机制、易错题集三组补齐接口。
> 分页结构与全局约定一致：`data = { list: [], pagination: { page, page_size, total, total_pages } }`。
> 数据库变更：新增表 `user_notifications`（迁移 `2026_09_15_100002`）；`user_wrong_questions` 加列
> `right_streak`（连续答对次数）/ `mastered_at`（掌握时间）（迁移 `2026_09_15_100003`），状态语义沿用 1=在错题本 2=已移除 3=已掌握。

### 消息通知中心 · API-MSG

### API-MSG-001 `GET /notifications`（需登录）
- query：`page`、`page_size`、`type?`（通知类型筛选）、`is_read?`（0 未读 / 1 已读筛选）
- 数据源：`user_notifications`（软删过滤，强制 `user_id=本人`），按 `created_at` 降序
- resp：`{ list: Notification[], pagination }`
- `Notification`：
```jsonc
{
  "id": 101, "type": 3, "title": "订单支付成功",
  "content": "您购买的「一建法规·年卡」已开通", "biz_type": "order",
  "biz_id": 5001, "is_read": 0, "read_at": null,
  "created_at": "2026-09-15 10:00:00"
}
```
- 枚举 `type`：1 系统通知 / 2 互动通知 / 3 业务通知（数值输出，文本映射前端做）
- 枚举 `is_read`：0 未读 / 1 已读；`read_at` 已读时间（未读为 `null`）

### API-MSG-002 `GET /notifications/unread-count`（需登录）
- resp：`{ "count": 5 }`（本人未读通知条数）

### API-MSG-003 `PUT /notifications/{id}/read`（需登录）
- 标记单条已读：置 `is_read=1`、`read_at=now`
- 仅本人记录可操作，找不到（含他人记录）抛 `DATA_NOT_FOUND`；幂等——已读再调返回成功不报错
- resp：`{ "marked": true }`

### API-MSG-004 `PUT /notifications/read-all`（需登录）
- 全部已读：批量更新本人全部未读记录
- resp：`{ "marked": 12 }`（本次标记条数，无未读时为 0）

### 斩题机制 · API-MST

> 斩题为作答联动，无独立提交接口：错题（status=1）**连续答对 3 次**自动置为已掌握（status=3），
> 期间再答错则计数归零；已掌握题目再答错恢复为在错题本。联动在作答（API-QUE-002）事务内完成，
> 仅对存在错题记录的题目生效。

### API-MST-001 `GET /mastered-questions`（需登录）
- query：`page`、`page_size`(默认10，≤50)、`bank_id?`、`keyword?`(题干模糊，已转义 %/_)
- 数据源：`user_wrong_questions`（status=3 已掌握，软删过滤）JOIN `question_items` JOIN `bank_question_banks`
- resp：`{ list: MasteredItem[], pagination }`
- `MasteredItem`（扁平风格，同 §十 FavoriteItem）：
```jsonc
{
  "id": 66, "question_id": 8801, "bank_id": 1024, "bank_name": "一建法规",
  "question_title": "题干文本", "question_type": 1,
  "question_options": [ { "key": "A", "content": "选项" } ],
  "question_difficulty": 2,
  "wrong_count": 4, "right_streak": 3, "mastered_at": "2026-09-15 10:00:00"
}
```
- 排序：`mastered_at` 降序
- 枚举 `question_type`：1 单选 / 2 多选 / 3 判断 / 4 填空 / 5 简答
- 枚举 `question_difficulty`：1 易 / 2 中 / 3 难

### API-MST-002 `PUT /mastered-questions/{id}/restore`（需登录）
- 找回：仅本人 + `status=3` 的记录可找回，否则抛 `DATA_NOT_FOUND`；
  置回 `status=1 在错题本`，并清零 `right_streak=0`、`mastered_at=null`
- resp：`{ "restored": true }`
- 枚举（错题状态，沿用 WrongQuestionStatus）：1 在错题本 / 2 已移除 / 3 已掌握

### 易错题集 · API-ERR

### API-ERR-001 `GET /error-prone-questions`（需登录）
- query：`bank_id*`（必填，易错题按题库维度查看，缺失抛 `PARAM_INVALID`）、`page`、`page_size`(默认10，≤50)、`limit_top?`（前 N 截断，本期服务端未使用，预留）
- 校验：题库不存在抛 `BANK_NOT_FOUND`；非本人私有题库抛 `FORBIDDEN`（官方题库 user_id=0 放行）
- 数据源：`question_items` 全站冗余统计列（`answer_count`/`right_count`/`correct_rate`），
  `status=1` 且未软删且 `answer_count>0`，按 `correct_rate` 升序、`answer_count` 降序
- resp：`{ list: ErrorProneItem[], pagination }`
- `ErrorProneItem`：
```jsonc
{
  "id": 8801, "bank_id": 1024,
  "question_title": "题干文本（stem_preview 优先，为空取 stem 截断 100 字）",
  "question_type": 1,
  "question_options": [ { "key": "A", "content": "选项" } ],
  "question_difficulty": 2,
  "correct_rate": 32.50, "answer_count": 200, "is_wrong": true
}
```
- `correct_rate` 为全站百分比数值（如 32.50，非本人正确率）；`is_wrong` 为布尔——该题在本人的错题本中且 status=1（服务端一次 in 查询批量判定）
- 枚举 `question_type`：1 单选 / 2 多选 / 3 判断 / 4 填空 / 5 简答
- 枚举 `question_difficulty`：1 易 / 2 中 / 3 难

---

## 十二、实现落地文件
- Service：`server/app/Services/Api/{UserProfileService,ImportService,QuestionPracticeService,WrongQuestionService,FavoriteNoteService,ExamService,MemberService,OrderService,QuestionSearchService,NotificationService}.php`
- Controller：`server/app/Http/Controllers/Api/V1/{User/ProfileController,Import/ImportController,Bank/QuestionPracticeController,Wrong/WrongQuestionController,Exam/ExamController,Member/MemberController,Order/OrderController,Search/SearchController,Notification/NotificationController}.php`
- 路由：`server/routes/client.php`（追加 member/orders/search/notifications 区块及错题域 MST/ERR 路由）

---

## 十三、反馈接口（API-FBK-001）

### API-FBK-001 `POST /feedbacks`（需登录，auth:client + user.active）
- 请求参数：
```jsonc
{
  "type": 1,             // 必填，反馈类型：1 功能异常 / 2 体验建议 / 3 其他
  "content": "内容文本",   // 必填，5~500 字
  "images": [101, 102],  // 可选，file_assets id 数组，最多 9 张
  "contact": "联系方式"    // 可选，≤64 字
}
```
- 校验：任一不合法抛 `PARAM_INVALID`（10007）；images 仅允许引用本人上传且未删除的 file_assets
- 落库：`sys_feedbacks`（user_id=当前用户、type、content、images_json=JSON(images)、contact 默认 ''、status=0 待处理）
- resp：
```jsonc
{ "submitted": true, "message": "感谢反馈，我们会尽快处理" }
```
- 处理流程由总后台 API-ADM-104（`/admin-api/v1/feedbacks`）负责，客户端不提供查看接口
- 客户端落地：`client/src/api/feedback.ts` + `client/src/pages-sub/feedback/create.vue`（我的页「意见反馈」入口）
