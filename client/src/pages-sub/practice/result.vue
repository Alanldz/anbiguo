<template>
  <base-dev-placeholder
    page-id="P-10"
    title="练习结果"
    desc="一次练习结束后的成绩反馈：正确率、用时、逐题对错概览，并可直接进入错题重做。"
    :features="features"
  />
</template>

<script setup lang="ts">
/**
 * 练习结果（P-10）
 * 接口：API-QUE-002 提交作答（汇总）、API-WRG-001 错题列表
 */
const features = [
  '本次练习总览：答题数、答对数、正确率、总用时',
  '环形进度图展示正确率（配色见 styles/tokens.ts chartColors）',
  '逐题对错列表，点击可跳转查看原题与解析',
  '一键进入错题重做 / 全部错题本',
  '生成练习报告并分享（可选）'
]
</script>
