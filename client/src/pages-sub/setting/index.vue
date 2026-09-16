<template>
  <view class="setting">
    <!-- 账号安全（仅展示） -->
    <view class="setting__group">
      <text class="setting__group-title">账号安全</text>
      <view class="setting__row">
        <text class="setting__row-label">手机号</text>
        <text class="setting__row-value">{{ maskedMobile }}</text>
      </view>
      <view class="setting__row">
        <text class="setting__row-label">登录设备管理</text>
        <text class="setting__row-value">本机已登录</text>
      </view>
    </view>

    <!-- 通用 -->
    <view class="setting__group">
      <text class="setting__group-title">通用</text>
      <view class="setting__row" @tap="handleClearCache">
        <text class="setting__row-label">清理缓存</text>
        <text class="setting__row-arrow">›</text>
      </view>
    </view>

    <!-- 关于 -->
    <view class="setting__group">
      <text class="setting__group-title">关于</text>
      <view class="setting__row">
        <text class="setting__row-label">版本号</text>
        <text class="setting__row-value">1.0.0</text>
      </view>
      <view class="setting__row" @tap="handleAgreement('service')">
        <text class="setting__row-label">用户协议</text>
        <text class="setting__row-arrow">›</text>
      </view>
      <view class="setting__row" @tap="handleAgreement('privacy')">
        <text class="setting__row-label">隐私政策</text>
        <text class="setting__row-arrow">›</text>
      </view>
    </view>

    <!-- 危险区 -->
    <view class="setting__danger">
      <view class="setting__btn setting__btn--logout" @tap="handleLogout">退出登录</view>
      <view class="setting__btn setting__btn--cancel" @tap="handleCancelAccount">注销账号</view>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 设置（P-27）
 * 接口：API-USER-004 账号注销（cancelAccount）
 * 说明：
 *  - 清理缓存：保留登录态（token），清理其余本地缓存；
 *  - 退出登录：二次确认 → 清 token → 跳登录页（复用 useUserStore.logout）；
 *  - 注销账号：两次 uni.showModal 确认 → 调 cancelAccount → 清登录态 → 跳登录页。
 */
import { computed } from 'vue'
import { useUserStore } from '@/stores/user'
import { cancelAccount } from '@/api/favorite'
import { maskMobile } from '@/utils/format'
import { removeStorage, STORAGE_KEYS } from '@/utils/storage'

const userStore = useUserStore()

const maskedMobile = computed(() => (userStore.profile ? maskMobile(userStore.profile.mobile) : '未绑定'))

const VERSION = '1.0.0'

function handleClearCache() {
  uni.showModal({
    title: '清理缓存',
    content: '将清除本地缓存（保留登录状态），确定继续吗？',
    success: (modal) => {
      if (!modal.confirm) return
      ;(Object.keys(STORAGE_KEYS) as Array<keyof typeof STORAGE_KEYS>).forEach((key) => {
        if (key !== 'TOKEN') removeStorage(STORAGE_KEYS[key])
      })
      uni.showToast({ title: '缓存已清理', icon: 'none' })
    }
  })
}

/** 用户协议 / 隐私政策（API 无关静态页 pages-sub/agreement/index?type=service|privacy） */
function handleAgreement(name: 'service' | 'privacy') {
  uni.navigateTo({ url: `/pages-sub/agreement/index?type=${name}` })
}

/** 帮助中心（pages-sub/help/index，静态 FAQ） */
function goHelp() {
  uni.navigateTo({ url: '/pages-sub/help/index' })
}

function handleLogout() {
  uni.showModal({
    title: '退出登录',
    content: '确定要退出当前账号吗？',
    success: (modal) => {
      if (!modal.confirm) return
      userStore.logout()
      uni.reLaunch({ url: '/pages/auth/login' })
    }
  })
}

async function handleCancelAccount() {
  // 第一步确认：告知后果
  uni.showModal({
    title: '注销账号',
    content: '注销后账号数据将无法恢复，确定继续吗？',
    confirmColor: '#EF4444',
    success: (first) => {
      if (!first.confirm) return
      // 第二步确认：二次确认
      uni.showModal({
        title: '再次确认注销',
        content: '请再次确认，此操作不可撤销。',
        confirmColor: '#EF4444',
        success: async (second) => {
          if (!second.confirm) return
          await cancelAccount({ confirm: true })
          userStore.logout()
          uni.reLaunch({ url: '/pages/auth/login' })
        }
      })
    }
  })
}
</script>

<style lang="scss" scoped>
.setting {
  min-height: 100vh;
  padding: $spacing-lg;
  background-color: $color-bg-page;

  &__group {
    margin-bottom: $spacing-lg;
    background-color: $color-bg-card;
    border-radius: $radius-lg;
    overflow: hidden;
  }

  &__group-title {
    display: block;
    padding: $spacing-md $spacing-md 0;
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: $spacing-md;
    border-bottom: 2rpx solid $color-divider;

    &:last-child {
      border-bottom: none;
    }
  }

  &__row-label {
    font-size: $font-size-base;
    color: $color-text-primary;
  }

  &__row-value {
    font-size: $font-size-sm;
    color: $color-text-secondary;
  }

  &__row-arrow {
    font-size: $font-size-lg;
    color: $color-text-placeholder;
  }

  &__danger {
    margin-top: $spacing-xl;
  }

  &__btn {
    height: 88rpx;
    border-radius: $radius-lg;
    font-size: $font-size-base;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: $spacing-md;

    &--logout {
      color: $color-primary;
      background-color: $color-bg-card;
    }

    &--cancel {
      color: $color-danger;
      background-color: $color-bg-card;
    }
  }
}
</style>
