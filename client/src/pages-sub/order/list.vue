<template>
  <base-dev-placeholder
    page-id="P-24"
    title="我的订单"
    desc="查看会员、题库等交易订单，支持查看详情与申请退款。"
    :features="features"
  />
</template>

<script setup lang="ts">
/**
 * 我的订单（P-24）
 * 接口：API-ORD-001 我的订单列表
 */
const features = [
  '订单列表：订单号、商品、金额、状态、下单时间',
  '状态筛选：待支付 / 已支付 / 已取消 / 已退款',
  '待支付订单支持继续支付（重新拉起微信支付）',
  '已支付订单查看详情与开票信息（可选）',
  '申请退款入口，提交后由总后台处理'
]
</script>
