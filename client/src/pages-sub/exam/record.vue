<template>
  <base-dev-placeholder
    page-id="P-13"
    title="成绩单"
    desc="考试成绩反馈：得分、正确率、用时、按章节的正确率分析，以及错题入库入口。"
    :features="features"
  />
</template>

<script setup lang="ts">
/**
 * 成绩单（P-13）
 * 接口：API-EXM-004 成绩与试卷回顾
 */
const features = [
  '总得分与得分率，达线提示',
  '答题统计：正确 / 错误 / 未答 数量分布',
  '章节维度正确率分析（薄弱章节定位）',
  '本次用时与平均每题用时',
  '入口：查看试卷回顾（P-14）、错题加入错题本'
]
</script>
