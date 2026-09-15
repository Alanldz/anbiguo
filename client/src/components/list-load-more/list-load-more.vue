<template>
  <view class="list-load-more">
    <view v-if="loading" class="list-load-more__text">加载中…</view>
    <view v-else-if="!hasMore && showNoMore" class="list-load-more__text">没有更多了</view>
  </view>
</template>

<script setup lang="ts">
/**
 * 列表加载状态
 * 配合 uni-app onReachBottom 使用
 */
interface Props {
  loading?: boolean
  hasMore?: boolean
  showNoMore?: boolean
}

withDefaults(defineProps<Props>(), {
  loading: false,
  hasMore: true,
  showNoMore: true
})
</script>

<style lang="scss" scoped>
.list-load-more {
  padding: $spacing-md 0;

  &__text {
    text-align: center;
    font-size: $font-size-xs;
    color: $color-text-placeholder;
  }
}
</style>
