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

## 七、实现落地文件
- Service：`server/app/Services/Api/{UserProfileService,ImportService,QuestionPracticeService,WrongQuestionService,ExamService}.php`
- Controller：`server/app/Http/Controllers/Api/V1/{User/ProfileController,Import/ImportController,Bank/QuestionPracticeController,Wrong/WrongQuestionController,Exam/ExamController}.php`
- 路由：`server/routes/client.php`（替换「待开发」区块）
