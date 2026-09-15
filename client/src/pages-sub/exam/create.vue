<template>
  <base-dev-placeholder
    page-id="P-11"
    title="发起考试"
    desc="从当前题库按题型、章节、题量、时长组卷，生成试卷后进入考试。"
    :features="features"
  />
</template>

<script setup lang="ts">
/**
 * 发起考试 / 组卷（P-11）
 * 接口：API-EXM-001 发起试卷、API-BANK-003 题库详情（取章节）
 */
const features = [
  '选择题库（默认带入当前题库）',
  '组卷规则：题型占比、章节范围、题目数量、难度',
  '考试时长设置，支持随机抽题与固定顺序',
  '开始考试前展示试卷预览（总分、题量、时长）',
  '组卷成功后进入考试答题页 P-12'
]
</script>
