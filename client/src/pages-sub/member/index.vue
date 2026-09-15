<template>
  <base-dev-placeholder
    page-id="P-23"
    title="会员中心"
    desc="展示 VIP 会员权益与套餐，支持开通、续费与权益使用情况查询。"
    :features="features"
  />
</template>

<script setup lang="ts">
/**
 * 会员中心（P-23）
 * 接口：API-MBR-001 会员权益与套餐、API-MBR-002 开通会员下单、API-PAY-001 微信支付
 */
const features = [
  '会员权益展示：AI 功能次数、精简题、易错题、VIP 题库（45+ 项）',
  '套餐列表：月卡 / 季卡 / 年卡，展示原价与优惠价',
  '权益使用情况：AI 导题剩余次数、有效期',
  '下单走微信支付（API-PAY-001/002），回调后自动开通',
  '兑换码与优惠券入口'
]
</script>
