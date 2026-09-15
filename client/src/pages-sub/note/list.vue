<template>
  <base-dev-placeholder
    page-id="P-21"
    title="我的笔记"
    desc="查看与管理做过的题目笔记，点击可跳转回原题继续学习。"
    :features="features"
  />
</template>

<script setup lang="ts">
/**
 * 我的笔记（P-21）
 * 接口：API-QUE-004 写 / 改笔记
 */
const features = [
  '笔记列表：笔记内容、关联题干摘要、所属题库',
  '按题库、时间筛选，支持关键词搜索',
  '点击跳转原题（答题页定位到对应题目）',
  '编辑与删除笔记',
  '笔记可导出为文档（可选，后续迭代）'
]
</script>
