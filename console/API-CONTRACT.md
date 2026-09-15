# 用户电脑端后台（console）· 接口契约

> 本文件是 `console/web`（前端）与 `server/app/Http/Controllers/Console`（后端）的**唯一对接依据**。
> 任何字段增删必须先改本文件，再改两端代码。
> 相关规范：`docs/04-API接口规范与登记表.md` §四、`docs/06-后台隔离与权限设计.md` §二/§六。

---

## 0. 全局约定

| 项 | 约定 |
| --- | --- |
| 基路径 | `/console-api/v1` |
| 鉴权守卫 | `auth:console`（`JWT_SECRET_CONSOLE`，2 小时，不刷新） |
| 附带中间件 | `request.id`、`platform`、`console.user`（账号状态校验） |
| 响应结构 | 统一 `App\Support\ApiResponse`：`{code,message,data,request_id,timestamp}`，`code=0` 为成功 |
| 分页参数 | 请求 `page`（默认 1）、`page_size`（默认 20，上限 100） |
| 分页响应 | `data = { list: [], pagination: { page, page_size, total, total_pages } }` |
| 时间格式 | `Y-m-d H:i:s` 字符串，空值返回 `null` |
| 金额 | 字符串，保留 2 位小数（如 `"19.90"`） |
| 数据归属 | **所有接口必须以当前登录用户为边界**，越权访问抛 `BusinessException(ErrorCode::FORBIDDEN)` |
| 软删除 | 删除一律软删（`deleted_at`），不物理删除 |
| Token 请求头 | `Authorization: Bearer {token}`，`X-Client-Platform: console` |

### 前端本地存储

| 键 | 值 |
| --- | --- |
| `shitu_console_token` | JWT 字符串 |

### 端口约定

| 服务 | 端口 |
| --- | --- |
| `server/`（客户端 API + console API） | 8000 |
| `admin/api` | 8200 |
| `admin/web` | 8210 |
| `console/web` | 8230 |

`console/web` 开发期代理：`/console-api` → `http://localhost:8000`。

### 枚举文本映射（后端必须用 `App\Enums\` 输出 `*_text` 字段）

| 枚举 | 值 → 文本 |
| --- | --- |
| UserStatus | 1 正常 / 2 禁用 / 3 注销中 / 4 已注销 |
| MemberLevel | 0 普通 / 1 月卡 / 2 季卡 / 3 年卡 / 4 永久 |
| MemberStatus | 1 生效 / 2 已过期 / 3 已冻结 |
| BankSourceType | 1 用户上传 / 2 官方 / 3 购买 / 4 AI生成 |
| BankChargeType | 1 免费 / 2 会员免费 / 3 单独购买 |
| BankStatus | 1 正常 / 2 隐藏 / 3 待审核 / 4 已拒绝 |
| QuestionType | 1 单选 / 2 多选 / 3 判断 / 4 填空 / 5 简答 |
| QuestionDifficulty | 1 易 / 2 中 / 3 难 |
| QuestionSourceType | 1 手动录入 / 2 文档导入 / 3 拍照OCR / 4 AI生成 |
| ImportMode | 1 文档导入 / 2 手动录入 / 3 拍照OCR / 4 试题答案分离 |
| ImportTaskStatus | 1 待解析 / 2 解析中 / 3 待校对 / 4 已完成 / 5 失败 |
| WrongQuestionStatus | 1 在错题本 / 2 已移除 / 3 已掌握 |
| ExamStatus | 1 进行中 / 2 已交卷 / 3 超时自动交卷 / 4 已作废 |
| OrderType | 1 会员 / 2 题库购买 / 3 学习资料购买 |
| OrderStatus | 0 待支付 / 1 已支付 / 2 已取消 / 3 已退款 / 4 已关闭 |
| PayChannel | 1 微信支付 / 2 支付宝 |
| FileBizType | 1 题库源文件 / 2 题目图片 / 3 学习资料 / 4 课程音视频 / 5 头像 / 6 公开静态 / 9 临时文件 |
| FileStatus | 1 待上传 / 2 已上传 / 3 解析中 / 4 已归档 / 5 失败 |

### 公共对象

```jsonc
// UserBrief —— 当前登录用户
{
  "id": 12, "uid": "U100012", "mobile": "13800138000",
  "nickname": "张三", "avatar": "https://...",
  "member": { "level": 3, "level_text": "年卡", "expired_at": "2027-09-15 12:00:00" }
}
```

---

## 一、认证 · API-CSL-AUTH

### API-CSL-AUTH-001 `POST /auth/login` ✅已实现
- req：`{ mobile*, password?, code?, login_type? }`（`password` 与 `code` 二选一；`login_type`：1 密码 / 2 验证码）
- resp：`{ token, expires_in, user: UserBrief }`

### API-CSL-AUTH-002 `POST /auth/logout` ✅已实现（需登录）
- resp：`null`

### API-CSL-AUTH-003 `GET /auth/me` ✅已实现（需登录）
- resp：`UserBrief`

### API-CSL-AUTH-004 `POST /auth/sms-code` 🆕（免登录）
- req：`{ mobile*, scene? }`（`scene`：`login` 默认 / `bind` 换绑手机）
- resp：`{ debug_code: string|null }`（生产环境恒为 `null`，本地调试返回验证码）
- 限流：`throttle:console-login`

---

## 二、学习概览 · API-CSL-STAT

### API-CSL-STAT-001 `GET /statistics/overview`（需登录）
```jsonc
{
  "summary": {
    "bank_count": 12,             // 我的题库数
    "question_count": 3450,       // 我的题目总数
    "practice_count": 88,         // 累计练习次数
    "exam_count": 12,             // 累计考试次数
    "answer_count": 5200,         // 累计作答题数
    "right_count": 4100,
    "wrong_count": 1100,
    "correct_rate": "78.85",      // 字符串
    "duration_seconds": 93600,
    "study_days": 36,
    "wrong_question_count": 210,  // 错题本在册数
    "favorite_count": 45,
    "note_count": 18
  },
  "member": { "level": 3, "level_text": "年卡", "status": 1, "status_text": "生效",
              "expired_at": "2027-09-15 12:00:00", "ai_import_quota": 5 },
  "trend": [ { "date": "2026-08-17", "answer_count": 120, "right_count": 95, "duration_seconds": 3600 } ]
}
```
> `trend` 固定返回**近 30 天**（含无数据的日期，`answer_count=0`），按日期升序。

---

## 三、我的题库 · API-CSL-BANK

`BankItem`：
```jsonc
{
  "id": 1, "title": "一建法规精讲", "subtitle": "2026 版", "cover": "",
  "category_id": 3, "category_name": "建筑工程",
  "source_type": 1, "source_type_text": "用户上传",
  "charge_type": 1, "charge_type_text": "免费",
  "question_count": 320, "chapter_count": 8, "practice_count": 120, "user_count": 12,
  "status": 1, "status_text": "正常",
  "tags": ["法规", "一建"],
  "created_at": "2026-09-01 10:00:00", "updated_at": "2026-09-10 12:00:00"
}
```

| 编号 | 方法 | 路径 | 说明 |
| --- | --- | --- | --- |
| API-CSL-BANK-001 | GET | `/question-banks` | 列表 |
| API-CSL-BANK-002 | GET | `/question-banks/{id}` | 详情 |
| API-CSL-BANK-003 | POST | `/question-banks` | 新建 |
| API-CSL-BANK-004 | PUT | `/question-banks/{id}` | 更新 / 重命名 |
| API-CSL-BANK-005 | DELETE | `/question-banks/{id}` | 删除（级联软删题目与章节） |
| API-CSL-BANK-006 | GET | `/question-banks/{id}/export` | 导出 |
| API-CSL-BANK-007 | GET | `/bank-categories` | 分类树（下拉用） |

- 001 query：`page, page_size, keyword, category_id, source_type, status`
  resp：分页 `BankItem[]`
- 003 req：`{ title*, subtitle?, category_id?, cover?, charge_type? }` resp：`{ id }`
- 004 req：同上（全部可选） resp：`null`
- 006 resp：
```jsonc
{
  "bank": { "id": 1, "title": "一建法规精讲" },
  "exported_at": "2026-09-15 15:00:00",
  "questions": [
    { "question_type": 1, "stem": "题干", "analysis": "解析", "answer": "A",
      "difficulty": 1, "score": "2.00",
      "options": [ { "option_key": "A", "content": "选项A", "is_correct": 1 } ] }
  ]
}
```
> 前端自行转 Blob 下载为 `.json`。
- 007 resp：`[ { id, parent_id, name, code, level, children: [] } ]`

---

## 四、章节 · API-CSL-CHP

| 编号 | 方法 | 路径 | req | resp |
| --- | --- | --- | --- | --- |
| API-CSL-CHP-001 | GET | `/question-banks/{bankId}/chapters` | — | 扁平数组 `ChapterItem[]` |
| API-CSL-CHP-002 | POST | `/question-banks/{bankId}/chapters` | `{ name*, parent_id?, sort_order? }` | `{ id }` |
| API-CSL-CHP-003 | PUT | `/chapters/{id}` | `{ name?, sort_order? }` | `null` |
| API-CSL-CHP-004 | DELETE | `/chapters/{id}` | — | `null`（该章节下题目 chapter_id 置 0） |

`ChapterItem`：`{ id, bank_id, parent_id, name, level, question_count, sort_order }`

---

## 五、题目管理 · API-CSL-QST

`QuestionItem`：
```jsonc
{
  "id": 1001, "bank_id": 1, "chapter_id": 3, "chapter_name": "第一章 建设工程法规",
  "question_type": 1, "question_type_text": "单选",
  "stem": "题干富文本", "stem_preview": "题干摘要",
  "answer": "A", "difficulty": 1, "difficulty_text": "易", "score": "2.00",
  "status": 1, "source_type": 1, "source_type_text": "手动录入",
  "answer_count": 200, "right_count": 150, "correct_rate": "75.00",
  "sort_order": 0, "created_at": "2026-09-01 10:00:00"
}
```

| 编号 | 方法 | 路径 | 说明 |
| --- | --- | --- | --- |
| API-CSL-QST-001 | GET | `/question-banks/{bankId}/questions` | 列表 |
| API-CSL-QST-002 | GET | `/questions/{id}` | 详情（含 options） |
| API-CSL-QST-003 | POST | `/question-banks/{bankId}/questions` | 新增 |
| API-CSL-QST-004 | PUT | `/questions/{id}` | 更新 |
| API-CSL-QST-005 | DELETE | `/questions/{id}` | 删除 |
| API-CSL-QST-006 | POST | `/questions/batch-delete` | 批量删除 |
| API-CSL-QST-007 | POST | `/questions/batch-move` | 批量移动章节 |

- 001 query：`page, page_size, keyword, question_type, difficulty, chapter_id, status`
- 002 resp：`QuestionItem` + `{ "analysis": "解析", "options": [ { id, option_key, content, is_correct, sort_order } ] }`
- 003 / 004 req：
```jsonc
{
  "question_type*": 1, "stem*": "题干", "analysis": "解析", "answer*": "A",
  "difficulty": 1, "score": 2, "chapter_id": 0, "sort_order": 0,
  "options": [ { "option_key": "A", "content": "选项A", "is_correct": 1 } ]
}
```
> 单选 / 多选 / 判断必须带 `options`；填空 / 简答 `options` 传空数组。
> 写入后同步维护 `bank_question_banks.question_count` 与 `bank_chapters.question_count`。
- 006 req：`{ ids: number[] }`；007 req：`{ ids: number[], chapter_id: number }`

---

## 六、题库导入 · API-CSL-IMP

`ImportTaskItem`：
```jsonc
{
  "id": 7, "task_no": "IMP20260915000001", "bank_id": 1, "bank_title": "一建法规精讲",
  "origin_name": "2026法规真题.docx", "file_ext": "docx",
  "import_mode": 1, "import_mode_text": "文档导入",
  "total_count": 120, "success_count": 118, "fail_count": 2, "progress": 100,
  "status": 4, "status_text": "已完成", "error_message": "",
  "started_at": "2026-09-15 09:00:00", "finished_at": "2026-09-15 09:03:00",
  "created_at": "2026-09-15 09:00:00"
}
```

| 编号 | 方法 | 路径 | 说明 |
| --- | --- | --- | --- |
| API-CSL-IMP-001 | POST | `/import-tasks` | 创建导入任务 |
| API-CSL-IMP-002 | GET | `/import-tasks` | 任务列表 |
| API-CSL-IMP-003 | GET | `/import-tasks/{id}` | 任务详情（含解析结果） |
| API-CSL-IMP-004 | DELETE | `/import-tasks/{id}` | 删除任务 |
| API-CSL-IMP-005 | GET | `/import-tasks/template` | 导入模板说明 |

- 001 req：`{ file_id*, bank_id?, bank_title?, import_mode? }`（`bank_id=0` 且传 `bank_title` 时先建题库）
  resp：`{ id, task_no, status, status_text, bank_id }`
  > 本期为**同步占位实现**：任务创建后状态置 `3 待校对`，`result_json` 写入占位结构；真实 AI/文档解析在后续迭代接入（不影响前端联调）。
- 002 query：`page, page_size, status`
- 003 resp：`ImportTaskItem` + `{ "result": [ /* 待校对题目数组，结构同 QST 新增入参 */ ] }`
- 005 resp：`{ "columns": [ { "name": "题干", "required": true, "desc": "..." } ], "sample_url": "" }`

---

## 七、学习资料 · API-CSL-FIL

`FileAssetItem`：
```jsonc
{
  "id": 55, "category_id": 2, "category_name": "历年真题",
  "biz_type": 3, "biz_type_text": "学习资料",
  "origin_name": "2025一建真题.pdf", "file_ext": "pdf",
  "file_size": 2048000, "file_size_text": "1.95 MB",
  "mime_type": "application/pdf", "is_public": 0,
  "status": 2, "status_text": "已上传",
  "created_at": "2026-09-10 11:00:00"
}
```

| 编号 | 方法 | 路径 | 说明 |
| --- | --- | --- | --- |
| API-CSL-FIL-001 | GET | `/file-categories` | 分类列表 |
| API-CSL-FIL-002 | POST | `/file-categories` | 新建分类 |
| API-CSL-FIL-003 | PUT | `/file-categories/{id}` | 更新分类 |
| API-CSL-FIL-004 | DELETE | `/file-categories/{id}` | 删除分类 |
| API-CSL-FIL-005 | GET | `/file-assets` | 资料列表 |
| API-CSL-FIL-006 | POST | `/file-assets` | 上传后登记 |
| API-CSL-FIL-007 | DELETE | `/file-assets/{id}` | 删除资料 |
| API-CSL-FIL-008 | GET | `/file-assets/{id}/url` | 获取下载地址 |
| API-CSL-FIL-009 | POST | `/files/upload-token` | 七牛直传凭证 |

- 001 resp：`[ { id, parent_id, name, code, file_count, sort_order } ]`
- 002 req：`{ name*, parent_id?, sort_order? }`（`code` 由后端按名称生成，同用户内唯一） resp：`{ id }`
- 005 query：`page, page_size, keyword, category_id, biz_type` → 分页 `FileAssetItem[]`
- 006 req：`{ object_key*, origin_name*, file_ext?, file_size?, file_hash?, mime_type?, category_id?, biz_type?, is_public? }`
- 008 resp：`{ "url": "https://...", "expires_in": 3600 }`
- 009 req：`{ biz_type*, file_ext*, file_name*, category_id? }`
  resp：`{ provider, upload_token, upload_url, object_key, expires_in, max_size }`
  > 上传流程（`docs/05`）：009 取凭证 → 前端直传七牛 → 006 登记入库。

---

## 八、我的错题 · API-CSL-WRG

| 编号 | 方法 | 路径 | 说明 |
| --- | --- | --- | --- |
| API-CSL-WRG-001 | GET | `/wrong-questions` | 错题列表 |
| API-CSL-WRG-002 | DELETE | `/wrong-questions/{id}` | 移除单条 |
| API-CSL-WRG-003 | POST | `/wrong-questions/batch-remove` | 批量移除 |

- 001 query：`page, page_size, bank_id, keyword`（默认仅 `status=1 在错题本`）
  resp：分页
```jsonc
{ "id": 9, "question_id": 1001, "bank_id": 1, "bank_title": "一建法规精讲",
  "question_type": 1, "question_type_text": "单选", "stem_preview": "题干摘要",
  "wrong_count": 3, "last_wrong_at": "2026-09-12 20:00:00",
  "last_answer": "B", "status": 1, "status_text": "在错题本" }
```
- 003 req：`{ ids: number[] }`

---

## 九、考试记录 · API-CSL-EXM

| 编号 | 方法 | 路径 | 说明 |
| --- | --- | --- | --- |
| API-CSL-EXM-001 | GET | `/exam-records` | 记录列表 |
| API-CSL-EXM-002 | GET | `/exam-records/{id}` | 记录详情 |

- 001 query：`page, page_size, bank_id, keyword`
  resp：分页
```jsonc
{ "id": 3, "record_no": "ER20260915000001", "paper_title": "2026 法规模拟卷",
  "bank_id": 1, "bank_title": "一建法规精讲",
  "total_count": 100, "right_count": 78, "wrong_count": 20,
  "correct_rate": "78.00", "get_score": "78.00", "total_score": "100.00",
  "duration_seconds": 5400, "is_passed": 1, "status": 2, "status_text": "已交卷",
  "submitted_at": "2026-09-15 10:30:00", "created_at": "2026-09-15 09:00:00" }
```
- 002 resp：记录详情 + `{ "answers": [ { "question_id": 1001, "stem_preview": "题干摘要", "user_answer": "B", "is_correct": 0, "score": "0.00" } ] }`

---

## 十、订单与会员 · API-CSL-ORD

| 编号 | 方法 | 路径 | 说明 |
| --- | --- | --- | --- |
| API-CSL-ORD-001 | GET | `/orders` | 我的订单 |
| API-CSL-ORD-002 | GET | `/orders/{id}` | 订单详情 |
| API-CSL-ORD-003 | GET | `/member` | 我的会员 |
| API-CSL-ORD-004 | GET | `/member-plans` | 会员套餐 |

- 001 query：`page, page_size, status, order_type`
  resp：分页
```jsonc
{ "id": 88, "order_no": "OD20260915120000123456", "order_type": 1, "order_type_text": "会员",
  "biz_title": "年度会员", "origin_amount": "199.00", "discount_amount": "0.00",
  "pay_amount": "199.00", "pay_channel": 1, "pay_channel_text": "微信支付",
  "status": 1, "status_text": "已支付",
  "paid_at": "2026-09-15 12:01:00", "created_at": "2026-09-15 12:00:00" }
```
- 003 resp：`{ level, level_text, status, status_text, started_at, expired_at, ai_import_quota, source_type, source_type_text }`
- 004 resp：`[ { id, name, level, level_text, duration_days, price_amount, origin_amount, description, benefits: [], ai_import_quota, is_recommend } ]`

---

## 十一、账号设置 · API-CSL-ACC

| 编号 | 方法 | 路径 | 说明 |
| --- | --- | --- | --- |
| API-CSL-ACC-001 | GET | `/account/profile` | 个人资料 |
| API-CSL-ACC-002 | PUT | `/account/profile` | 更新资料 |
| API-CSL-ACC-003 | PUT | `/account/password` | 修改密码 |
| API-CSL-ACC-004 | PUT | `/account/mobile` | 换绑手机 |

- 001 resp：
```jsonc
{ "id": 12, "uid": "U100012", "mobile": "13800138000", "nickname": "张三", "avatar": "",
  "gender": 1, "birthday": "1995-06-01", "province": "广东省", "city": "深圳市",
  "exam_target": "一级建造师", "bio": "", "study_days": 36, "study_seconds": 93600,
  "created_at": "2026-01-01 00:00:00" }
```
- 002 req：`{ nickname?, avatar?, gender?, birthday?, province?, city?, exam_target?, bio? }`
- 003 req：`{ old_password?, new_password* }`（账号无密码时 `old_password` 可空）
- 004 req：`{ new_mobile*, code* }`（`code` 来自 `POST /auth/sms-code` 且 `scene=bind`）

---

## 十二、章节/页面与权限码对照（同步登记 `docs/06` §九）

| 页面路径 | 页面名称 | 所需权限码 |
| --- | --- | --- |
| `/login` | 用户后台登录 | — |
| `/dashboard` | 学习概览 | `console:dashboard` |
| `/banks` | 我的题库 | `console:bank:list` |
| `/banks/:id/questions` | 题目管理 | `console:question:list` |
| `/import` | 题库导入 | `console:import:create` |
| `/resources` | 学习资料 | `console:file:list` |
| `/wrong` | 我的错题 | `console:wrong:list` |
| `/exams` | 考试记录 | `console:exam:list` |
| `/orders` | 订单与会员 | `console:order:list` |
| `/account` | 账号设置 | `console:account:view` |

> 用户后台为**单用户自管**场景，权限码仅用于菜单显隐与前端路由守卫；后端以「数据归属」为硬边界，不做 RBAC 二次校验。
