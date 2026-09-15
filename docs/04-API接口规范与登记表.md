# 04 · API 接口规范与登记表

> **任何接口必须先在本文件登记编号，再写代码。** 未登记的接口视为不存在。

## 一、接口分域（三套接口互相隔离）

| 分域 | 前缀 | 使用方 | 鉴权守卫 | 文档章节 |
| --- | --- | --- | --- | --- |
| 客户端接口 | `/api/v1` | 小程序 / Android / H5 | `auth:client` | §三 |
| 用户后台接口 | `/console-api/v1` | 电脑端用户后台 | `auth:console` | §四 |
| 总后台接口 | `/admin-api/v1` | 系统管理员后台 | `auth:admin` | §五 |

> 总后台接口独立部署、独立密钥，**不得**与客户端接口共用任何 Token 体系。

## 二、通用规范

### 2.1 请求

- 协议 HTTPS，编码 UTF-8，数据格式 JSON（文件上传用 `multipart/form-data`）
- 必带请求头：

| Header | 说明 |
| --- | --- |
| `Authorization` | `Bearer {token}` |
| `X-Client-Platform` | `mp-weixin` / `app-android` / `h5` / `console` / `admin` |
| `X-Client-Version` | 客户端版本号，如 `1.0.0` |
| `X-Request-Id` | 链路追踪 ID，前端生成 UUID |

### 2.2 响应结构（统一）

```json
{
  "code": 0,
  "message": "success",
  "data": {},
  "request_id": "3f9c1a2e-...",
  "timestamp": 1789000000
}
```

- `code = 0` 表示成功，非 0 表示业务错误
- 分页数据统一结构：

```json
{
  "code": 0,
  "message": "success",
  "data": {
    "list": [],
    "pagination": { "page": 1, "page_size": 20, "total": 135, "total_pages": 7 }
  }
}
```

### 2.3 错误码分段（**全局唯一，不得重复**）

| 段位 | 归属 | 示例 |
| --- | --- | --- |
| `0` | 成功 | 0 |
| `1xxxx` | 通用 / 参数 / 鉴权 | 10001 参数错误、10401 登录失效、10403 无权限、10500 服务异常 |
| `2xxxx` | 用户域 | 20001 手机号已注册、20002 验证码错误、20003 会员已过期 |
| `3xxxx` | 题库 / 题目域 | 30001 题库不存在、30002 导入解析失败、30003 题目数量超限 |
| `4xxxx` | 考试域 | 40001 试卷不存在、40002 考试已结束、40003 重复交卷 |
| `5xxxx` | 交易 / 支付域 | 50001 订单不存在、50002 支付失败、50003 已支付 |
| `6xxxx` | 文件 / OSS 域 | 60001 文件类型不支持、60002 文件过大、60003 上传失败 |
| `7xxxx` | 第三方接口域 | 70001 短信发送失败、70002 微信接口异常、70003 AI 服务超时 |

### 2.4 版本与兼容

- 版本号进 URL 路径（`/v1/`），不通过 Header 传
- 破坏性变更升版本（`/v2/`），旧版本至少保留一个迭代周期

### 2.5 限流与幂等

- 短信、短信验证码：`60s/次，10次/天/手机号`
- AI 导题、AI 出题：按会员等级配额限流
- 支付回调、考试交卷：必须支持幂等（业务唯一键去重）

---

## 三、客户端接口登记表（`/api/v1`）

| 编号 | 模块 | 接口名称 | 方法 | 路径 | 鉴权 | 限流 | 状态 | 变更记录 |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| API-AUTH-001 | 认证 | 发送短信验证码 | POST | `/api/v1/auth/sms-code` | 否 | 60s/次 | 待开发 | 2026-09-15 建立 |
| API-AUTH-002 | 认证 | 手机号登录/注册 | POST | `/api/v1/auth/login` | 否 | — | 待开发 | 2026-09-15 建立 |
| API-AUTH-003 | 认证 | 微信小程序登录 | POST | `/api/v1/auth/wechat-login` | 否 | — | 待开发 | 2026-09-15 建立 |
| API-AUTH-004 | 认证 | 刷新 Token | POST | `/api/v1/auth/refresh` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-USER-001 | 用户 | 获取个人资料 | GET | `/api/v1/user/profile` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-USER-002 | 用户 | 更新个人资料 | PUT | `/api/v1/user/profile` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-USER-003 | 用户 | 我的学习空间统计 | GET | `/api/v1/user/study-summary` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-BANK-001 | 题库 | 题库分类列表 | GET | `/api/v1/bank-categories` | 否 | — | 待开发 | 2026-09-15 建立 |
| API-BANK-002 | 题库 | 我的题库列表 | GET | `/api/v1/question-banks` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-BANK-003 | 题库 | 题库详情 | GET | `/api/v1/question-banks/{id}` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-BANK-004 | 题库 | 创建题库 | POST | `/api/v1/question-banks` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-BANK-005 | 题库 | 更新/重命名题库 | PUT | `/api/v1/question-banks/{id}` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-BANK-006 | 题库 | 删除题库 | DELETE | `/api/v1/question-banks/{id}` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-BANK-007 | 题库 | 题库市场列表 | GET | `/api/v1/bank-market` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-IMP-001 | 导入 | 上传文档导题 | POST | `/api/v1/import/upload` | 是 | 配额 | 待开发 | 2026-09-15 建立 |
| API-IMP-002 | 导入 | 查询解析进度 | GET | `/api/v1/import/tasks/{id}` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-IMP-003 | 导入 | 下载导入模板 | GET | `/api/v1/import/template` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-IMP-004 | 导入 | 手动录入题目 | POST | `/api/v1/import/manual` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-IMP-005 | 导入 | 拍照录题（OCR） | POST | `/api/v1/import/ocr` | 是 | 配额 | 待开发 | 2026-09-15 建立 |
| API-QUE-001 | 题目 | 题目列表（练习取题） | GET | `/api/v1/question-banks/{id}/questions` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-QUE-002 | 题目 | 提交单题作答 | POST | `/api/v1/questions/{id}/answer` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-QUE-003 | 题目 | 收藏/取消收藏 | POST | `/api/v1/questions/{id}/favorite` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-QUE-004 | 题目 | 写/改笔记 | PUT | `/api/v1/questions/{id}/note` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-QUE-005 | 题目 | 试题报错 | POST | `/api/v1/questions/{id}/report` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-WRG-001 | 错题 | 错题列表 | GET | `/api/v1/wrong-questions` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-WRG-002 | 错题 | 移除错题 | DELETE | `/api/v1/wrong-questions/{id}` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-EXM-001 | 考试 | 发起/生成试卷 | POST | `/api/v1/exam-papers` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-EXM-002 | 考试 | 试卷详情（含题目） | GET | `/api/v1/exam-papers/{id}` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-EXM-003 | 考试 | 交卷 | POST | `/api/v1/exam-records` | 是 | 幂等 | 待开发 | 2026-09-15 建立 |
| API-EXM-004 | 考试 | 成绩与试卷回顾 | GET | `/api/v1/exam-records/{id}` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-EXM-005 | 考试 | 考试记录列表 | GET | `/api/v1/exam-records` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-SRC-001 | 搜索 | 题库内搜索试题 | GET | `/api/v1/search/questions` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-SRC-002 | 搜索 | 拍照/文字搜题 | POST | `/api/v1/search/solve` | 是 | 配额 | 待开发 | 2026-09-15 建立 |
| API-FIL-001 | 文件 | 获取 OSS 直传凭证 | POST | `/api/v1/files/upload-token` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-FIL-002 | 文件 | 上传完成回调登记 | POST | `/api/v1/files/complete` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-FIL-003 | 文件 | 学习资料列表 | GET | `/api/v1/file-assets` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-MBR-001 | 会员 | 会员权益与套餐 | GET | `/api/v1/member/plans` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-MBR-002 | 会员 | 开通会员下单 | POST | `/api/v1/member/orders` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-ORD-001 | 订单 | 我的订单列表 | GET | `/api/v1/orders` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-PAY-001 | 支付 | 微信支付统一下单 | POST | `/api/v1/pay/wechat/prepay` | 是 | — | 待开发 | 2026-09-15 建立 |
| API-PAY-002 | 支付 | 微信支付回调 | POST | `/api/v1/pay/wechat/notify` | 否（验签） | — | 待开发 | 2026-09-15 建立 |
| API-CFG-001 | 配置 | 客户端启动配置 | GET | `/api/v1/config/boot` | 否 | — | 待开发 | 2026-09-15 建立 |

## 四、用户后台接口登记表（`/console-api/v1`）

| 编号 | 模块 | 接口名称 | 方法 | 路径 | 状态 |
| --- | --- | --- | --- | --- | --- |
| API-CSL-AUTH-001 | 认证 | 用户后台登录 | POST | `/console-api/v1/auth/login` | 待开发 |
| API-CSL-AUTH-002 | 认证 | 退出登录 | POST | `/console-api/v1/auth/logout` | 待开发 |
| API-CSL-BANK-001 | 题库 | 题库列表（分页/搜索） | GET | `/console-api/v1/question-banks` | 待开发 |
| API-CSL-BANK-002 | 题库 | 题库详情与题目管理 | GET | `/console-api/v1/question-banks/{id}` | 待开发 |
| API-CSL-BANK-003 | 题库 | 批量导入 | POST | `/console-api/v1/question-banks/import` | 待开发 |
| API-CSL-FIL-001 | 文件 | 资料分类管理 | GET/POST | `/console-api/v1/file-categories` | 待开发 |
| API-CSL-FIL-002 | 文件 | 资料上传与管理 | POST | `/console-api/v1/file-assets` | 待开发 |
| API-CSL-ORD-001 | 订单 | 我的订单 | GET | `/console-api/v1/orders` | 待开发 |
| API-CSL-STAT-001 | 统计 | 学习数据看板 | GET | `/console-api/v1/statistics/overview` | 待开发 |

## 五、总后台接口登记表（`/admin-api/v1`，独立部署）

| 编号 | 模块 | 接口名称 | 方法 | 路径 | 状态 |
| --- | --- | --- | --- | --- | --- |
| API-ADM-AUTH-001 | 认证 | 管理员登录（独立密钥） | POST | `/admin-api/v1/auth/login` | 待开发 |
| API-ADM-AUTH-002 | 认证 | 管理员登出 | POST | `/admin-api/v1/auth/logout` | 待开发 |
| API-ADM-USR-001 | 用户 | 用户列表/详情/封禁 | GET/PUT | `/admin-api/v1/users` | 待开发 |
| API-ADM-BANK-001 | 题库 | 官方题库管理 | GET/POST/PUT/DELETE | `/admin-api/v1/question-banks` | 待开发 |
| API-ADM-BANK-002 | 题库 | 内容审核（UGC） | PUT | `/admin-api/v1/question-banks/{id}/audit` | 待开发 |
| API-ADM-CAT-001 | 分类 | 分类管理 | GET/POST/PUT/DELETE | `/admin-api/v1/categories` | 待开发 |
| API-ADM-CFG-001 | 配置 | 配置分组列表 | GET | `/admin-api/v1/configs` | 待开发 |
| API-ADM-CFG-002 | 配置 | 修改配置（短信/微信/OSS/支付/AI） | PUT | `/admin-api/v1/configs/{group}` | 待开发 |
| API-ADM-CFG-003 | 配置 | 测试配置连通性 | POST | `/admin-api/v1/configs/{group}/test` | 待开发 |
| API-ADM-ORD-001 | 订单 | 订单管理/退款 | GET/POST | `/admin-api/v1/orders` | 待开发 |
| API-ADM-OPR-001 | 运营 | Banner/推荐位管理 | GET/POST/PUT/DELETE | `/admin-api/v1/banners` | 待开发 |
| API-ADM-SYS-001 | 系统 | 管理员与角色权限 | GET/POST/PUT/DELETE | `/admin-api/v1/admins` | 待开发 |
| API-ADM-SYS-002 | 系统 | 操作日志 | GET | `/admin-api/v1/logs` | 待开发 |
| API-ADM-SYS-003 | 系统 | 数据看板 | GET | `/admin-api/v1/statistics/overview` | 待开发 |

## 六、第三方接口登记表（统一由配置中心驱动）

| 编号 | 名称 | 提供方 | 用途 | 配置项（`sys_configs`） | 状态 |
| --- | --- | --- | --- | --- | --- |
| EXT-SMS-001 | 短信发送 | 阿里云/腾讯云 | 验证码、通知 | `sms.provider` `sms.access_key` `sms.secret` `sms.sign_name` `sms.template_code` | 待接入 |
| EXT-WX-001 | 小程序登录 | 微信开放平台 | code2Session | `wechat.mp_app_id` `wechat.mp_secret` | 待接入 |
| EXT-WX-002 | 微信支付 | 微信支付商户平台 | 会员/题库支付 | `payment.mch_id` `payment.api_key` `payment.cert_path` `payment.notify_url` | 待接入 |
| EXT-OSS-001 | 对象存储 | **七牛云 Kodo** | 文件存储、前端直传 | `storage.provider` `storage.access_key` `storage.secret_key` `storage.bucket` `storage.domain` `storage.region` `storage.token_expire` | 待接入 |
| EXT-AI-001 | AI 导题/出题 | 大模型服务商 | 文档解析、题目生成 | `ai.provider` `ai.api_key` `ai.model` `ai.base_url` `ai.daily_quota` | 待接入 |
| EXT-OCR-001 | OCR 识别 | 第三方 | 拍照录题 | `ocr.provider` `ocr.app_id` `ocr.secret` | 待接入 |

> **存储抽象层约定**：业务代码只调用 `StorageService` 统一接口（`uploadToken()` / `put()` / `url()` / `delete()`），底层驱动可为七牛 / 阿里云 / 本地，通过 `storage.provider` 切换，业务层不感知具体厂商，避免后期更换存储商需要改动业务代码。

> **所有 KEY / SECRET 不写进代码、不写进仓库**，一律存 `sys_configs`（AES 加密）并在总后台可视化修改，详见 `06` 文档。

## 七、接口变更登记

| 日期 | 接口编号 | 变更类型 | 变更内容 | 执行人 | 兼容性 |
| --- | --- | --- | --- | --- | --- |
| 2026-09-15 | — | 初始化 | 建立接口规范与三域登记台账 | — | — |
| 2026-09-15 | API-AUTH-005 | 新增 | 客户端新增「退出登录」接口（jti 黑名单即时失效） | — | 向后兼容 |
| 2026-09-15 | API-CSL-AUTH-003 | 新增 | 用户后台新增「当前登录用户信息」接口 | — | 向后兼容 |
| 2026-09-15 | API-FIL-004 | 新增 | 新增「私有文件签名下载地址」接口 | — | 向后兼容 |
| 2026-09-15 | 1xxxx | 扩充 | 错误码由示例值扩充为完整清单（10001~10500），见 `app/Support/ErrorCode.php` | — | 向后兼容 |

---

## 八、实现进度登记（代码落地状态）

> 台账状态说明：`待开发` = 仅登记未实现；`已开发` = 代码已落地待联调；`已联调` = 前后端已通；`已上线` = 已发生产。
> 每完成一个接口，必须同步更新本表与 §三/§四/§五 对应行的状态列。

| 编号 | 路径 | 状态 | 落地文件 |
| --- | --- | --- | --- |
| API-AUTH-001 | POST `/api/v1/auth/sms-code` | 已开发 | `Api/V1/User/AuthController::sendSmsCode` |
| API-AUTH-002 | POST `/api/v1/auth/login` | 已开发 | `Api/V1/User/AuthController::login` |
| API-AUTH-003 | POST `/api/v1/auth/wechat-login` | 已开发 | `Api/V1/User/AuthController::wechatLogin` |
| API-AUTH-004 | POST `/api/v1/auth/refresh` | 已开发 | `Api/V1/User/AuthController::refresh` |
| API-AUTH-005 | POST `/api/v1/auth/logout` | 已开发 | `Api/V1/User/AuthController::logout` |
| API-CFG-001 | GET `/api/v1/config/boot` | 已开发 | `Api/V1/Common/ConfigController::boot` |
| API-BANK-001 | GET `/api/v1/bank-categories` | 已开发 | `Api/V1/Bank/BankCategoryController::index` |
| API-BANK-002 | GET `/api/v1/question-banks` | 已开发 | `Api/V1/Bank/QuestionBankController::index` |
| API-BANK-003 | GET `/api/v1/question-banks/{id}` | 已开发 | `Api/V1/Bank/QuestionBankController::show` |
| API-BANK-004 | POST `/api/v1/question-banks` | 已开发 | `Api/V1/Bank/QuestionBankController::store` |
| API-BANK-005 | PUT `/api/v1/question-banks/{id}` | 已开发 | `Api/V1/Bank/QuestionBankController::update` |
| API-BANK-006 | DELETE `/api/v1/question-banks/{id}` | 已开发 | `Api/V1/Bank/QuestionBankController::destroy` |
| API-BANK-007 | GET `/api/v1/bank-market` | 已开发 | `Api/V1/Bank/QuestionBankController::market` |
| API-FIL-001 | POST `/api/v1/files/upload-token` | 已开发 | `Api/V1/File/FileController::uploadToken` |
| API-FIL-002 | POST `/api/v1/files/complete` | 已开发 | `Api/V1/File/FileController::complete` |
| API-FIL-003 | GET `/api/v1/files/assets` | 已开发 | `Api/V1/File/FileController::index` |
| API-FIL-004 | GET `/api/v1/files/assets/{id}/url` | 已开发 | `Api/V1/File/FileController::signedUrl` |
| API-CSL-AUTH-001 | POST `/console-api/v1/auth/login` | 已开发 | `Console/V1/AuthController::login` |
| API-CSL-AUTH-002 | POST `/console-api/v1/auth/logout` | 已开发 | `Console/V1/AuthController::logout` |
| API-CSL-AUTH-003 | GET `/console-api/v1/auth/me` | 已开发 | `Console/V1/AuthController::me` |

**当前进度：20 / 46 接口已开发**（客户端 17，用户后台 3）。

### 8.1 待补齐清单（按优先级）

| 优先级 | 模块 | 接口 |
| --- | --- | --- |
| P0 | 导入（核心差异化） | API-IMP-001~005 |
| P0 | 题目与练习 | API-QUE-001~005、API-WRG-001~002 |
| P0 | 考试 | API-EXM-001~005 |
| P0 | 用户 | API-USER-001~003 |
| P1 | 搜索 | API-SRC-001~002 |
| P1 | 会员 / 订单 / 支付 | API-MBR-001~002、API-ORD-001、API-PAY-001~002 |
| P1 | 用户后台其余 | API-CSL-BANK-001~003、API-CSL-FIL-001~002、API-CSL-ORD-001、API-CSL-STAT-001 |
| P2 | 总后台全部 | API-ADM-*（独立应用 `admin/api/`） |

### 8.2 尚未实现的服务端能力

| 能力 | 现状 | 影响 |
| --- | --- | --- |
| 腾讯云短信驱动 | 仅阿里云实现 | 若选腾讯云需补 `SmsService::sendByTencent()` |
| 微信支付 | 未实现 | `order_payments` 表结构已就绪，接口未开发 |
| AI 导题解析 | 未实现 | `question_import_tasks` 表结构已就绪，Job 未开发 |
| OCR 拍照录题 | 未实现 | 配置项已就绪 |
| 定时任务命令 | 调度已注册，命令未实现 | 需补 `member:expire-scan` 等 5 个命令，否则 `schedule:run` 会报「命令不存在」 |
| 出口 IP 白名单 / TOTP 二次验证 | 未实现 | 总后台安全加固项，见 `docs/06` §八 |
