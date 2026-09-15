<template>
  <base-dev-placeholder
    page-id="P-08"
    title="题库市场"
    desc="浏览官方题库与创作者上架的题库，按考试分类筛选，一键添加到我的题库。"
    :features="features"
  />
</template>

<script setup lang="ts">
/**
 * 题库市场（P-08）
 * 接口：API-BANK-007 题库市场列表、API-BANK-001 分类列表
 */
const features = [
  '按分类浏览：建筑、财会、考公、驾考、医考等全题库',
  '搜索考试名称，快速定位目标题库',
  '题库详情页：题量、来源、更新时间、用户评价',
  '一键添加到我的题库，支持付费题库下单',
  '运营位：热门推荐、新品上架、限时免费'
]
</script>
