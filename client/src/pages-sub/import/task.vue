<template>
  <base-dev-placeholder
    page-id="P-18"
    title="解析进度与校对"
    desc="查看文档解析进度，解析完成后逐题校对识别结果，确认无误再入库。"
    :features="features"
  />
</template>

<script setup lang="ts">
/**
 * 解析进度与校对（P-18）
 * 接口：API-IMP-002 查询解析进度、API-IMP-001 重试
 * 说明：解析为异步任务（ParseQuestionDocumentJob），前端轮询进度
 */
const features = [
  '解析进度条：已解析 / 总题数，实时轮询刷新',
  '解析失败展示失败原因，支持一键重试',
  '校对列表：逐题查看识别结果，可修改题干、选项、答案、解析',
  '批量操作：批量删除、批量设置章节',
  '确认入库后跳转题库详情页，展示最终题目数'
]
</script>
