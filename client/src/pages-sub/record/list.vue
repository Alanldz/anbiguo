<template>
  <base-dev-placeholder
    page-id="P-25"
    title="练习记录"
    desc="练习历史与学习数据统计：练习次数、正确率、学习时长与趋势。"
    :features="features"
  />
</template>

<script setup lang="ts">
/**
 * 练习记录（P-25）
 * 接口：API-USER-003 学习统计、API-CSL-STAT-001 学习数据看板
 */
const features = [
  '练习概览：累计练习次数、总正确率、累计学习时长',
  '练习记录列表：题库、练习模式、用时、正确率、时间',
  '近 7 天 / 30 天练习趋势图（图表配色见 styles/tokens.ts）',
  '按题库维度统计掌握程度',
  '导出学习报告（可选，后续迭代）'
]
</script>
