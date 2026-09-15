<template>
  <base-dev-placeholder
    page-id="P-26"
    title="学习资料"
    desc="题库下挂的学习资料管理：按分类浏览文档、讲义与音视频，支持上传与下载。"
    :features="features"
  />
</template>

<script setup lang="ts">
/**
 * 学习资料（P-26）
 * 接口：API-FIL-003 学习资料列表、API-FIL-001/002 上传
 * 目录分类规范见 docs/05-OSS存储与文件分类规范.md
 */
const features = [
  '资料分类树浏览（file_categories，编码小写下划线）',
  '上传文档 / 讲义 / 音视频，走七牛直传（凭证由后端签发）',
  '按业务类型区分：题库源文件、题目图片、学习资料、课程音视频',
  '文件列表：名称、大小、类型、上传时间，支持下载与删除',
  '私密文件走签名 URL 下载，不开放公共读'
]
</script>
