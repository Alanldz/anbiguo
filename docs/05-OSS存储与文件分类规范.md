# 05 · OSS 存储与文件分类规范

> 目标：用户上传的文档**归类清晰、可追溯、可批量清理**，任何文件都能通过路径反推归属业务。
> 所有文件元信息同时登记进 `file_assets` 表（见 `03` 文档）。

## 一、存储结构（Bucket 内目录树）

```
{oss-bucket}/
├── avatar/                                  头像
│   └── {user_id}/{yyyyMM}/{filename}
├── bank/                                    题库相关
│   └── {bank_id}/
│       ├── source/                          用户上传的原始文档（Word/Excel/PDF/TXT）
│       ├── image/                           题目内嵌图片、公式图
│       ├── ocr/                             拍照录题原图
│       └── export/                          导出文件（错题导出、题库导出）
├── question/                                题目级附属文件
│   └── {question_id}/{filename}
├── resource/                                学习资料（挂在题库下）
│   └── {bank_id}/{category_code}/{yyyyMM}/{filename}
├── course/                                  AI 课程 / 音视频
│   └── {course_id}/
│       ├── video/
│       ├── audio/
│       └── doc/
├── public/                                  公开静态资源（图标、分享海报模板）
│   └── {module}/{filename}
├── template/                                官方导入模板（Excel/Word）
│   └── {filename}
├── report/                                  试题报错截图
│   └── {yyyyMM}/{filename}
├── feedback/                                意见反馈截图
│   └── {yyyyMM}/{filename}
└── temp/                                    临时文件（定期清理）
    └── {yyyyMMdd}/{filename}
```

## 二、目录与文件名规范

### 2.1 目录

- 全部小写；层级用 `/`，不使用中文
- 时间维度统一 `yyyyMM`（如 `202609`），需要日粒度用 `yyyyMMdd`
- 业务 ID 直接作为目录名，不带前缀字母（`bank/1024/source/`）

### 2.2 文件名

统一格式：

```
{业务前缀}_{主体ID}_{yyyyMMddHHmmss}_{随机6位}.{扩展名}
```

示例：

| 场景 | 文件名 |
| --- | --- |
| 题库源文档 | `bank_1024_20260915113025_a7f3c1.xlsx` |
| 题目图片 | `qimg_8801_20260915113210_b2d9e4.png` |
| 学习资料 | `res_1024_20260915114002_c8a1f0.pdf` |
| 课程视频 | `video_55_20260915115030_e1b7d3.mp4` |
| 头像 | `avatar_2077_20260915120000_f4c2a8.jpg` |
| 导出文件 | `export_1024_20260915121000_9d3e6b.pdf` |

业务前缀对照表：

| 前缀 | 用途 | 前缀 | 用途 |
| --- | --- | --- | --- |
| `avatar` | 用户头像 | `video` | 视频 |
| `bank` | 题库源文档 | `audio` | 音频 |
| `qimg` | 题目图片 | `res` | 学习资料 |
| `ocr` | 拍照录题原图 | `export` | 导出文件 |
| `tpl` | 官方模板 | `report` | 报错截图 |
| `fb` | 反馈截图 | `tmp` | 临时文件 |

> 禁止：中文名、空格、`空格/`、`(1)`、`副本`、`新建文件夹`、超过 128 字符的文件名。

## 三、文件类型与限制

| 业务类型 `FileBizType` | 允许扩展名 | 大小上限 | 是否公开读 |
| --- | --- | --- | --- |
| 1 题库源文件 | doc, docx, xls, xlsx, pdf, txt, csv | 50 MB | 私有 |
| 2 题目图片 | jpg, jpeg, png, gif, webp | 10 MB | 私有（带签名 URL） |
| 3 学习资料 | doc, docx, pdf, ppt, pptx, mp4, mp3, zip | 200 MB | 按题库权限 |
| 4 课程音视频 | mp4, mov, m4a, mp3 | 1 GB | 私有（防盗链） |
| 5 头像 | jpg, png, webp | 5 MB | 公开读 |
| 6 公开静态 | png, jpg, svg, json | 5 MB | 公开读 |
| 9 临时文件 | 同业务白名单 | 同业务 | 私有，7 天自动清理 |

校验规则：

1. **双重校验**：前端校验 + 服务端校验（MIME + 扩展名 + 文件头魔数），不只信扩展名
2. 大小超限直接拒绝并返回 `60002 文件过大`
3. 类型不符返回 `60001 文件类型不支持`
4. 文件名一律服务端重新生成，**不使用用户原始文件名作为存储路径**

## 四、上传流程（前端直传七牛云，后端签发上传凭证）

> 存储服务商：**七牛云 Kodo**。业务代码统一走 `StorageService` 抽象层，厂商可替换。

```
① 前端请求 POST /api/v1/files/upload-token
   参数：biz_type、ext、size、可选 bank_id
② 后端校验：登录态 → 类型白名单 → 大小 → 会员配额
   生成 object_key（按本文档规则）→ 签发七牛上传凭证 uploadToken
   （scope 限定到目标 key 前缀、deadline 15 分钟、可带 returnBody 回调）
③ 前端凭 uploadToken 直传七牛（不经过业务服务器，节省带宽）
④ 上传成功回调 POST /api/v1/files/complete
   后端写 file_assets 记录：object_key、biz_type、size、hash(md5)、owner、状态=已上传
⑤ 需要异步处理的（导题解析）投递队列 Job：ParseQuestionDocumentJob
```

要点：

- **禁止**把 AccessKey / SecretKey 下发到前端，只下发**限定前缀与时效**的上传凭证
- 七牛空间（Bucket）按业务拆分为公开空间与私有空间：`public` 空间走 CDN 直链，`private` 空间全部走**私有下载签名 URL**（有效期 5~30 分钟）
- `object_key` 即七牛空间内的 key，命名规则与本文档 §二 完全一致，不使用用户原始文件名
- 私有文件下载由后端生成签名 URL 返回，前端不持有签名密钥
- 视频类绑定绑定的域名开启防盗链（Referer 白名单）+ 时限签名；必要时接入 CDN
- 上传凭证有效期默认 15 分钟，配置项 `storage.token_expire` 可在总后台调整

### 4.1 存储抽象层接口（Laravel 侧）

```php
interface StorageDriverInterface
{
    public function uploadToken(string $objectKey, int $expireSeconds): string;   // 签发直传凭证
    public function put(string $objectKey, string $localPath): bool;              // 服务端上传
    public function url(string $objectKey, int $expireSeconds = 0): string;       // 获取访问/签名 URL
    public function delete(string $objectKey): bool;                              // 删除对象
    public function exists(string $objectKey): bool;                              // 是否存在
}
```

驱动实现：`QiniuDriver`（默认）/ `AliyunDriver` / `LocalDriver`；由 `storage.provider` 决定，业务层零感知。

## 五、`file_assets` 表字段约定（对应 `03` 文档）

| 字段 | 说明 |
| --- | --- |
| `id` | 主键 |
| `user_id` | 归属用户 |
| `biz_type` | 业务类型，对应 `FileBizType` |
| `bank_id` | 归属题库（可空） |
| `category_id` | 归属文件分类（可空） |
| `origin_name` | 用户原始文件名（仅留档展示） |
| `object_key` | OSS 路径（唯一） |
| `file_ext` | 扩展名 |
| `file_size` | 字节数 |
| `file_hash` | MD5，用于秒传与去重 |
| `storage` | 存储方：oss / cos / local |
| `status` | 1=待上传 2=已上传 3=解析中 4=已归档 5=失败 |
| `created_at / updated_at / deleted_at` | 时间 |

## 六、文件分类管理（业务侧）

- `file_categories` 表维护分类树：分类编码 `category_code`（小写下划线，如 `exam_paper`、`lecture_note`）
- 用户在用户后台可自建分类，为题库资料归类
- 删除分类时，其下文件**不可物理删除**，先迁移到「未分类」，二次确认后才清理
- 总后台可查看全站文件用量统计（按用户 / 按业务类型 / 按月份）

## 七、清理与归档策略

| 对象 | 策略 |
| --- | --- |
| `temp/` 临时文件 | 上传后 7 天自动清理 |
| 软删除的文件 | 保留 30 天，期间可恢复；到期后删除 OSS 对象 |
| 用户注销 | 账号数据软删；OSS 文件 90 天后清理 |
| 归档 | 超过 1 年且无引用的文件迁移至低频存储（IA），降低费用 |

## 八、登记要求

- 每新增一个 OSS 目录前缀，必须在本文档 §一 / §二 中登记
- 每新增一个业务类型，必须同步更新 `FileBizType` 枚举与 §三 表格
- 变更记录写入本节下方：

| 日期 | 变更内容 | 执行人 |
| --- | --- | --- |
| 2026-09-15 | 建立 OSS 目录分类与命名规范 | — |
