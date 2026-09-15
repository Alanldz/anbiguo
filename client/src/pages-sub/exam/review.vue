<template>
  <base-dev-placeholder
    page-id="P-14"
    title="试卷回顾"
    desc="逐题回顾考试作答：显示我的答案、正确答案与解析，错题可一键加入错题本。"
    :features="features"
  />
</template>

<script setup lang="ts">
/**
 * 试卷回顾（P-14）
 * 接口：API-EXM-004 试卷回顾（含作答明细）
 */
const features = [
  '逐题展示题干、选项、我的作答与正确答案',
  '答对答错颜色区分（正确绿 / 错误红，遵循统一设计变量）',
  '展示试题解析与知识点标签',
  '一键将错题加入错题本',
  '支持只看错题 / 全部题目切换'
]
</script>
