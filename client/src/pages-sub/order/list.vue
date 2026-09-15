<template>
  <view class="order">
    <!-- 状态筛选 Tab -->
    <view class="order__tabs">
      <view
        v-for="tab in tabs"
        :key="tab.value"
        class="order__tab"
        :class="{ 'is-active': activeStatus === tab.value }"
        @tap="handleTab(tab.value)"
      >
        {{ tab.label }}
      </view>
    </view>

    <!-- 订单列表 -->
    <view class="order__list">
      <template v-if="list.length">
        <view v-for="item in list" :key="item.id" class="order__item">
          <view class="order__item-header">
            <text class="order__item-type">{{ item.order_type_text }}</text>
            <text class="order__item-status" :class="statusClass(item.status)">{{ item.status_text }}</text>
          </view>
          <text class="order__item-title">{{ item.biz_title }}</text>
          <view class="order__item-amount-row">
            <text class="order__item-amount">¥{{ item.pay_amount }}</text>
            <text v-if="item.discount_amount > 0" class="order__item-discount">已优惠 ¥{{ item.discount_amount }}</text>
          </view>
          <view class="order__item-footer">
            <view class="order__item-meta">
              <text class="order__item-no">订单号 {{ item.order_no }}</text>
              <text class="order__item-time">{{ item.created_at }}</text>
            </view>
            <text
              v-if="item.status === OrderStatus.Unpaid"
              class="order__item-pay"
              @tap="handlePay(item)"
            >去支付</text>
          </view>
        </view>
      </template>

      <base-empty
        v-else-if="!loading"
        title="暂无相关订单"
        desc="开通会员后订单会显示在这里"
        icon-text="单"
      />

      <list-load-more :loading="loading" :has-more="hasMore" />
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 我的订单（P-24）
 * 接口：API-ORD-001 我的订单列表（GET /orders，分页 + status 筛选）
 * 说明：状态 Tab（全部/待支付/已支付/已退款）+ 订单卡片；
 *       待支付订单「去支付」依赖微信支付（API-PAY-001/002 未接入），点击提示。
 */
import { ref } from 'vue'
import { onReachBottom } from '@dcloudio/uni-app'
import { fetchOrders } from '@/api/order'
import { OrderStatus } from '@/types'
import type { OrderItem } from '@/types'

const tabs = [
  { label: '全部', value: -1 },
  { label: '待支付', value: OrderStatus.Unpaid },
  { label: '已支付', value: OrderStatus.Paid },
  { label: '已退款', value: OrderStatus.Refunded }
]

const list = ref<OrderItem[]>([])
const loading = ref(false)
const hasMore = ref(true)
const page = ref(1)
const pageSize = 20
/** -1 表示全部 */
const activeStatus = ref(-1)

onReachBottom(() => loadData(false))

async function loadData(reset = false) {
  if (reset) {
    page.value = 1
    hasMore.value = true
    list.value = []
  }
  if (loading.value || (!reset && !hasMore.value)) return
  loading.value = true
  try {
    const res = await fetchOrders({
      page: page.value,
      page_size: pageSize,
      status: activeStatus.value >= 0 ? activeStatus.value : undefined
    })
    list.value = reset ? res.list : [...list.value, ...res.list]
    page.value += 1
    hasMore.value = res.pagination.page < res.pagination.total_pages
  } finally {
    loading.value = false
  }
}

function handleTab(value: number) {
  if (activeStatus.value === value) return
  activeStatus.value = value
  loadData(true)
}

function statusClass(status: OrderStatus): string {
  if (status === OrderStatus.Unpaid) return 'is-warning'
  if (status === OrderStatus.Paid) return 'is-success'
  if (status === OrderStatus.Refunded) return 'is-info'
  return 'is-muted'
}

function handlePay(_item: OrderItem) {
  // TODO: 重新拉起微信支付（API-PAY-001/002 待接入）
  uni.showToast({ title: '支付功能待微信支付接入，敬请期待', icon: 'none' })
}
</script>

<style lang="scss" scoped>
.order {
  min-height: 100vh;
  background-color: $color-bg-page;

  &__tabs {
    position: sticky;
    top: 0;
    z-index: 10;
    display: flex;
    padding: 0 $spacing-md;
    background-color: $color-bg-card;
  }

  &__tab {
    flex: 1;
    padding: $spacing-md 0;
    text-align: center;
    font-size: $font-size-sm;
    color: $color-text-regular;
    border-bottom: 4rpx solid transparent;

    &.is-active {
      color: $color-primary;
      font-weight: 600;
      border-bottom-color: $color-primary;
    }
  }

  &__list {
    padding: $spacing-lg;
  }

  &__item {
    padding: $spacing-md;
    margin-bottom: $spacing-sm;
    background-color: $color-bg-card;
    border-radius: $radius-lg;
    box-shadow: $shadow-sm;
  }

  &__item-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: $spacing-xs;
  }

  &__item-type {
    font-size: $font-size-xs;
    color: $color-text-secondary;
    background-color: $color-bg-page;
    padding: 2rpx 12rpx;
    border-radius: $radius-sm;
  }

  &__item-status {
    font-size: $font-size-xs;

    &.is-warning {
      color: $color-warning;
    }

    &.is-success {
      color: $color-success;
    }

    &.is-info {
      color: $color-text-secondary;
    }

    &.is-muted {
      color: $color-text-placeholder;
    }
  }

  &__item-title {
    display: block;
    font-size: $font-size-base;
    font-weight: 600;
    color: $color-text-primary;
  }

  &__item-amount-row {
    display: flex;
    align-items: baseline;
    margin-top: $spacing-xs;
  }

  &__item-amount {
    font-size: $font-size-lg;
    font-weight: 700;
    color: $color-text-primary;
  }

  &__item-discount {
    margin-left: $spacing-sm;
    font-size: $font-size-xs;
    color: $color-danger;
  }

  &__item-footer {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-top: $spacing-sm;
    padding-top: $spacing-sm;
    border-top: 1rpx solid $color-divider;
  }

  &__item-meta {
    display: flex;
    flex-direction: column;
  }

  &__item-no {
    font-size: $font-size-xs;
    color: $color-text-regular;
  }

  &__item-time {
    margin-top: 4rpx;
    font-size: $font-size-xs;
    color: $color-text-placeholder;
  }

  &__item-pay {
    font-size: $font-size-xs;
    color: $color-text-inverse;
    background-color: $color-danger;
    padding: 10rpx 32rpx;
    border-radius: 28rpx;
  }
}
</style>
