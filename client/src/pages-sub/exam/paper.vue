<template>
  <base-dev-placeholder
    page-id="P-12"
    title="考试答题"
    desc="限时考试进行页：倒计时、答题卡、标记题目、自动交卷与手动交卷。"
    :features="features"
  />
</template>

<script setup lang="ts">
/**
 * 考试答题（P-12）
 * 接口：API-EXM-002 试卷详情、API-EXM-003 交卷（幂等）
 */
const features = [
  '顶部倒计时，剩余 5 分钟高亮提醒，超时自动交卷',
  '答题卡弹层：已答 / 未答 / 标记状态区分',
  '标记题目，便于回头复查',
  '切屏检测与提醒（App / 小程序能力抽象）',
  '交卷前二次确认，提示未答题数量',
  '交卷请求后端幂等，防止重复提交'
]
</script>
