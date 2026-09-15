<template>
  <view class="nav-bar" :class="[`nav-bar--${theme}`, { 'nav-bar--fixed': fixed }]">
    <view class="nav-bar__placeholder" :style="{ height: `${statusBarHeight}px` }" />
    <view class="nav-bar__inner" :style="{ height: `${innerHeight}rpx` }">
      <view v-if="showBack" class="nav-bar__back" @tap="handleBack">
        <text class="nav-bar__back-icon">‹</text>
      </view>
      <view class="nav-bar__title text-ellipsis">{{ title }}</view>
      <view class="nav-bar__right">
        <slot name="right" />
      </view>
    </view>
    <!-- 占位，避免内容被固定导航栏遮挡 -->
    <view v-if="fixed" class="nav-bar__holder" :style="{ height: `${statusBarHeight}px` }" />
  </view>
</template>

<script setup lang="ts">
/**
 * 自定义导航栏
 * 多端统一：小程序/App/H5 均使用 navigationStyle: custom 时靠本组件渲染
 */
import { computed, ref } from 'vue'
import { getStatusBarHeight } from '@/utils/platform'

interface Props {
  title?: string
  theme?: 'light' | 'primary' | 'transparent'
  showBack?: boolean
  fixed?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  theme: 'light',
  showBack: true,
  fixed: true
})

const emit = defineEmits<{ (e: 'back'): void }>()

const statusBarHeight = ref(getStatusBarHeight())
const innerHeight = computed(() => 88)

function handleBack() {
  emit('back')
  const pages = getCurrentPages()
  if (pages.length > 1) {
    uni.navigateBack()
  } else {
    uni.switchTab({ url: '/pages/index/index' })
  }
}
</script>

<style lang="scss" scoped>
.nav-bar {
  &--light {
    background-color: $color-bg-card;
    .nav-bar__title,
    .nav-bar__back-icon {
      color: $color-text-primary;
    }
  }

  &--primary {
    background: $color-primary-gradient;
    .nav-bar__title,
    .nav-bar__back-icon {
      color: $color-text-inverse;
    }
  }

  &--transparent {
    background-color: transparent;
    .nav-bar__title,
    .nav-bar__back-icon {
      color: $color-text-inverse;
    }
  }

  &--fixed {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100;
  }

  &__inner {
    display: flex;
    align-items: center;
    padding: 0 $spacing-md;
  }

  &__back {
    width: 56rpx;
    height: 100%;
    display: flex;
    align-items: center;
  }

  &__back-icon {
    font-size: 56rpx;
    line-height: 1;
    margin-top: -6rpx;
  }

  &__title {
    flex: 1;
    text-align: center;
    font-size: $font-size-lg;
    font-weight: 600;
  }

  &__right {
    min-width: 56rpx;
    display: flex;
    justify-content: flex-end;
    align-items: center;
  }
}
</style>
