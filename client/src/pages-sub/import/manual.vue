<template>
  <base-dev-placeholder
    page-id="P-17"
    title="手动导题"
    desc="按标准题型手动录入单题：题干、选项、答案、解析、章节与难度。"
    :features="features"
  />
</template>

<script setup lang="ts">
/**
 * 手动导题（P-17）
 * 接口：API-IMP-004 手动录入题目
 */
const features = [
  '题型切换：单选 / 多选 / 判断 / 填空 / 简答',
  '题干支持图文混排，可插入题目图片（七牛直传）',
  '选项动态增删（单选/多选），答案勾选或输入',
  '解析、章节、难度、知识点标签填写',
  '保存并继续录入下一题，支持草稿本地暂存'
]
</script>
