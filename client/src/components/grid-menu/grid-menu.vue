<template>
  <view class="grid-menu" :class="`grid-menu--col-${columns}`">
    <view v-for="item in items" :key="item.key" class="grid-menu__item" @tap="handleTap(item)">
      <view class="grid-menu__icon" :style="{ backgroundColor: item.bgColor || defaultBgColor }">
        <text class="grid-menu__icon-text" :style="{ color: item.color || colorPrimary }">
          {{ item.iconText || item.label.slice(0, 1) }}
        </text>
        <view v-if="item.badge" class="grid-menu__badge">{{ item.badge }}</view>
      </view>
      <text class="grid-menu__label">{{ item.label }}</text>
      <text v-if="item.desc" class="grid-menu__desc">{{ item.desc }}</text>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 功能宫格
 * 复用于：首页二级入口、题库详情功能宫格、我的页推荐/更多功能
 * 图标当前以首字占位，UI 资源就绪后替换为 <image>（登记见 docs/01 §7.3）
 */
import { colorPrimaryVar } from '@/styles/tokens'

export interface GridMenuItem {
  key: string
  label: string
  desc?: string
  /** 图标文本占位 */
  iconText?: string
  color?: string
  bgColor?: string
  badge?: string
  path?: string
}

interface Props {
  items: GridMenuItem[]
  columns?: 3 | 4 | 5
}

withDefaults(defineProps<Props>(), { columns: 4 })
const emit = defineEmits<{ (e: 'select', item: GridMenuItem): void }>()

const colorPrimary = colorPrimaryVar
const defaultBgColor = 'rgba(43, 124, 255, 0.08)'

function handleTap(item: GridMenuItem) {
  emit('select', item)
  if (item.path) uni.navigateTo({ url: item.path })
}
</script>

<style lang="scss" scoped>
.grid-menu {
  display: flex;
  flex-wrap: wrap;
  background-color: $color-bg-card;
  border-radius: $radius-lg;
  padding: $spacing-md 0;

  &__item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: $spacing-sm 0;
  }

  &--col-3 &__item {
    width: 33.333%;
  }
  &--col-4 &__item {
    width: 25%;
  }
  &--col-5 &__item {
    width: 20%;
  }

  &__icon {
    position: relative;
    width: 84rpx;
    height: 84rpx;
    border-radius: $radius-lg;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  &__icon-text {
    font-size: $font-size-lg;
    font-weight: 600;
  }

  &__badge {
    position: absolute;
    top: -10rpx;
    right: -10rpx;
    min-width: 32rpx;
    height: 32rpx;
    padding: 0 6rpx;
    border-radius: 16rpx;
    background-color: $color-danger;
    color: $color-text-inverse;
    font-size: 20rpx;
    line-height: 32rpx;
    text-align: center;
  }

  &__label {
    margin-top: $spacing-xs;
    font-size: $font-size-sm;
    color: $color-text-regular;
  }

  &__desc {
    font-size: $font-size-xs;
    color: $color-text-secondary;
    margin-top: 2rpx;
  }
}
</style>
