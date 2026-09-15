<template>
  <view class="placeholder">
    <nav-bar :title="title" theme="light" />

    <view class="placeholder__body">
      <view class="placeholder__card">
        <view class="placeholder__header">
          <text class="placeholder__page-id">{{ pageId }}</text>
          <text class="placeholder__status">开发中</text>
        </view>
        <text class="placeholder__title">{{ title }}</text>
        <text class="placeholder__desc">{{ desc }}</text>
      </view>

      <view v-if="features.length" class="placeholder__card">
        <text class="placeholder__section">规划功能</text>
        <view v-for="(feature, index) in features" :key="index" class="placeholder__feature">
          <text class="placeholder__feature-dot" />
          <text class="placeholder__feature-text">{{ feature }}</text>
        </view>
      </view>

      <text class="placeholder__note">
        页面已按规范完成路由登记（{{ pageId }}），接口定义已在 src/api 中就绪，待后端接口联调后即可填充实现。
      </text>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 开发中占位页（通用）
 * 用途：路由已登记、工程已搭建但功能未实现的页面统一使用本组件，
 *       保证 pages.json 路由完整可用，同时明确告知当前进度。
 * 规范：页面完成后必须移除本组件引用，并在 docs/01 §7.2 把状态改为「已完成」。
 */
interface Props {
  pageId: string
  title: string
  desc?: string
  features?: string[]
}

withDefaults(defineProps<Props>(), {
  desc: '',
  features: () => []
})
</script>

<style lang="scss" scoped>
.placeholder {
  min-height: 100vh;
  background-color: $color-bg-page;

  &__body {
    padding: $spacing-lg;
  }

  &__card {
    padding: $spacing-lg;
    margin-bottom: $spacing-md;
    background-color: $color-bg-card;
    border-radius: $radius-lg;
  }

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: $spacing-sm;
  }

  &__page-id {
    font-size: $font-size-xs;
    color: $color-primary;
    background-color: $color-primary-bg;
    padding: 4rpx 12rpx;
    border-radius: $radius-sm;
  }

  &__status {
    font-size: $font-size-xs;
    color: $color-warning;
    background-color: $color-warning-bg;
    padding: 4rpx 12rpx;
    border-radius: $radius-sm;
  }

  &__title {
    display: block;
    font-size: $font-size-lg;
    font-weight: 600;
    color: $color-text-primary;
  }

  &__desc {
    display: block;
    margin-top: $spacing-xs;
    font-size: $font-size-sm;
    line-height: 1.7;
    color: $color-text-secondary;
  }

  &__section {
    display: block;
    font-size: $font-size-base;
    font-weight: 600;
    color: $color-text-primary;
    margin-bottom: $spacing-sm;
  }

  &__feature {
    display: flex;
    align-items: flex-start;
    padding: $spacing-xs 0;
  }

  &__feature-dot {
    width: 12rpx;
    height: 12rpx;
    border-radius: $radius-circle;
    background-color: $color-primary-light;
    margin: 14rpx $spacing-sm 0 0;
    flex-shrink: 0;
  }

  &__feature-text {
    flex: 1;
    font-size: $font-size-sm;
    line-height: 1.7;
    color: $color-text-regular;
  }

  &__note {
    display: block;
    padding: 0 $spacing-sm;
    font-size: $font-size-xs;
    line-height: 1.8;
    color: $color-text-placeholder;
  }
}
</style>
