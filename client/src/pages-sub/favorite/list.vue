<template>
  <base-dev-placeholder
    page-id="P-20"
    title="我的收藏"
    desc="集中管理收藏的题目，支持按题库筛选与批量取消收藏。"
    :features="features"
  />
</template>

<script setup lang="ts">
/**
 * 我的收藏（P-20）
 * 接口：API-QUE-003 收藏 / 取消收藏
 */
const features = [
  '收藏题目列表，展示题干、题型、来源题库',
  '按题库、题型、收藏时间筛选',
  '点击进入答题页查看解析',
  '左滑或长按取消收藏，支持批量管理',
  '收藏题目可加入自建练习集'
]
</script>
