<template>
  <view class="base-empty">
    <view class="base-empty__icon">
      <text class="base-empty__icon-text">{{ iconText }}</text>
    </view>
    <text class="base-empty__title">{{ title }}</text>
    <text v-if="desc" class="base-empty__desc">{{ desc }}</text>
    <slot />
  </view>
</template>

<script setup lang="ts">
/**
 * 空状态占位
 * 复用于：未选择题库、无错题、无收藏、搜索无结果等
 */
interface Props {
  title?: string
  desc?: string
  /** 图标文本占位，UI 资源就绪后替换 */
  iconText?: string
}

withDefaults(defineProps<Props>(), {
  title: '暂无数据',
  desc: '',
  iconText: '空'
})
</script>

<style lang="scss" scoped>
.base-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: $spacing-xl * 2 $spacing-lg;

  &__icon {
    width: 160rpx;
    height: 160rpx;
    border-radius: $radius-circle;
    background-color: $color-primary-bg;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: $spacing-lg;
  }

  &__icon-text {
    font-size: 56rpx;
    color: $color-primary-light;
    font-weight: 600;
  }

  &__title {
    font-size: $font-size-lg;
    font-weight: 600;
    color: $color-text-primary;
  }

  &__desc {
    margin-top: $spacing-sm;
    font-size: $font-size-sm;
    color: $color-text-secondary;
    text-align: center;
    line-height: 1.6;
  }
}
</style>
