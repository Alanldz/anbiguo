<template>
  <base-dev-placeholder
    page-id="P-15"
    title="考试记录"
    desc="历史考试成绩列表，可查看每次考试的成绩单与试卷回顾。"
    :features="features"
  />
</template>

<script setup lang="ts">
/**
 * 考试记录（P-15）
 * 接口：API-EXM-005 考试记录列表
 */
const features = [
  '按时间倒序展示历史考试记录',
  '每条记录展示试卷名称、得分、正确率、用时',
  '支持按题库筛选、按时间范围筛选',
  '点击进入成绩单（P-13）与试卷回顾（P-14）',
  '分页加载，配合上拉加载更多'
]
</script>
