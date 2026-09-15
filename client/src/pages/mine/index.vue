<template>
  <view class="mine">
    <!-- 顶部用户区 -->
    <view class="mine__header" :style="{ paddingTop: `${statusBarHeight + 12}px` }">
      <view class="mine__topbar">
        <text class="mine__switch" @tap="goBankList">选择题库 ▾</text>
        <view class="mine__topbar-actions">
          <text class="mine__topbar-icon" @tap="handleScan">扫</text>
          <text class="mine__topbar-icon" @tap="handleNotice">铃</text>
          <text class="mine__topbar-icon" @tap="goSetting">设</text>
        </view>
      </view>

      <view class="mine__user" @tap="goLogin">
        <view class="mine__avatar">
          <text class="mine__avatar-text">{{ avatarText }}</text>
        </view>
        <view class="mine__user-main">
          <view class="mine__user-row">
            <text class="mine__nickname">{{ profile?.nickname ?? '点击登录' }}</text>
            <text v-if="profile?.is_creator" class="mine__badge">认证创作者 ›</text>
          </view>
          <text class="mine__uid">UID: {{ profile?.uid ?? '--' }}</text>
        </view>
      </view>

      <!-- 会员卡 -->
      <view class="mine__vip">
        <view class="mine__vip-header">
          <text class="mine__vip-title">VIP会员享 45+ 项权益</text>
          <text class="mine__vip-more" @tap="goMember">会员中心 ›</text>
        </view>
        <view class="mine__vip-benefits">
          <view v-for="item in vipBenefits" :key="item" class="mine__vip-benefit">
            <text class="mine__vip-benefit-icon">V</text>
            <text class="mine__vip-benefit-label">{{ item }}</text>
          </view>
        </view>
        <view class="mine__vip-btn" @tap="goMember">立即开通</view>
      </view>
    </view>

    <view class="mine__body">
      <!-- 学习数据 -->
      <grid-menu :items="dataEntries" :columns="4" @select="handleGrid" />

      <view class="section-title">
        <text class="section-title__text">推荐功能</text>
      </view>
      <grid-menu :items="recommendEntries" :columns="4" @select="handleGrid" />

      <view class="section-title">
        <text class="section-title__text">更多功能</text>
      </view>
      <grid-menu :items="moreEntries" :columns="4" @select="handleGrid" />

      <view class="mine__footer">
        <text class="mine__footer-text">识途刷题 v1.0.0</text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 我的页（P-05）
 * 结构：用户信息 + 会员卡 + 学习数据 + 推荐功能 + 更多功能
 * 接口：API-USER-001 个人资料、API-USER-003 学习统计
 */
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useUserStore } from '@/stores/user'
import { getStatusBarHeight } from '@/utils/platform'
import type { GridMenuItem } from '@/components/grid-menu/grid-menu.vue'

const userStore = useUserStore()
const statusBarHeight = ref(getStatusBarHeight())
const profile = computed(() => userStore.profile)
const avatarText = computed(() => (profile.value ? profile.value.nickname.slice(0, 1) : '登'))

const vipBenefits = ['AI功能', '精简题', '易错题', 'VIP题库']

const dataEntries: GridMenuItem[] = [
  { key: 'wrong', label: '全部错题', iconText: '错', color: '#EF4444', bgColor: '#FDECEC', path: '/pages-sub/wrong/list' },
  { key: 'favorite', label: '全部收藏', iconText: '藏', color: '#F59E0B', bgColor: '#FFF5E6', path: '/pages-sub/favorite/list' },
  { key: 'record', label: '练习记录', iconText: '录', color: '#22C55E', bgColor: '#E8F8EE', path: '/pages-sub/record/list' },
  { key: 'download', label: '文档下载', iconText: '载', color: '#2B7CFF', bgColor: '#EAF2FF', path: '/pages-sub/resource/list' }
]

const recommendEntries: GridMenuItem[] = [
  { key: 'order', label: '我的订单', iconText: '单', path: '/pages-sub/order/list' },
  { key: 'sell', label: '内容出售', iconText: '售', path: '' },
  { key: 'creator', label: '创作者中心', iconText: '创', path: '' },
  { key: 'activity', label: '活动中心', iconText: '活', path: '' }
]

const moreEntries: GridMenuItem[] = [
  { key: 'group', label: '我的群组', iconText: '群', path: '' },
  { key: 'invite', label: '邀请码', iconText: '邀', path: '' },
  { key: 'coupon', label: '优惠券', iconText: '券', path: '' },
  { key: 'report', label: '试题报错', iconText: '报', path: '' },
  { key: 'feedback', label: '意见反馈', iconText: '馈', path: '' },
  { key: 'help', label: '帮助中心', iconText: '助', path: '' },
  { key: 'web', label: '电脑网页版', iconText: '网', path: '' },
  { key: 'redeem', label: '兑换码', iconText: '兑', path: '' },
  { key: 'trash', label: '回收站', iconText: '收', path: '/pages-sub/bank/recycle' },
  { key: 'master', label: '我的斩题', iconText: '斩', path: '/pages-sub/master/list' }
]

onShow(() => {
  userStore.fetchProfileIfLogged()
})

function handleGrid(item: GridMenuItem) {
  if (item.path) {
    uni.navigateTo({ url: item.path })
    return
  }
  const tip = item.key === 'web' ? '电脑端后台为独立站点，开发中' : `${item.label} 开发中`
  uni.showToast({ title: tip, icon: 'none' })
}

function goLogin() {
  if (!userStore.isLogged) uni.navigateTo({ url: '/pages/auth/login' })
}

function goMember() {
  uni.navigateTo({ url: '/pages-sub/member/index' })
}

function goSetting() {
  uni.navigateTo({ url: '/pages-sub/setting/index' })
}

function goBankList() {
  uni.switchTab({ url: '/pages/bank/list' })
}

function handleScan() {
  uni.scanCode({
    success: (res) => console.log('[mine] scan result:', res.result),
    fail: () => uni.showToast({ title: '扫码已取消', icon: 'none' })
  })
}

function handleNotice() {
  uni.showToast({ title: '消息中心开发中', icon: 'none' })
}
</script>

<style lang="scss" scoped>
.mine {
  min-height: 100vh;
  padding-bottom: $spacing-xl;
  background-color: $color-bg-page;

  &__header {
    background: linear-gradient(180deg, #fdf3e3 0%, #fff9f0 100%);
    padding: 0 $spacing-lg $spacing-lg;
  }

  &__topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: $spacing-sm 0;
  }

  &__switch {
    font-size: $font-size-base;
    color: $color-text-primary;
    font-weight: 500;
  }

  &__topbar-actions {
    display: flex;
    align-items: center;
  }

  &__topbar-icon {
    width: 60rpx;
    height: 60rpx;
    margin-left: $spacing-sm;
    border-radius: $radius-circle;
    background-color: rgba(0, 0, 0, 0.04);
    color: $color-text-regular;
    font-size: $font-size-sm;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  &__user {
    display: flex;
    align-items: center;
    padding: $spacing-md 0;
  }

  &__avatar {
    width: 112rpx;
    height: 112rpx;
    border-radius: $radius-circle;
    background-color: $color-primary-bg;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: $spacing-md;
  }

  &__avatar-text {
    font-size: $font-size-xl;
    color: $color-primary;
    font-weight: 600;
  }

  &__user-main {
    flex: 1;
    min-width: 0;
  }

  &__user-row {
    display: flex;
    align-items: center;
  }

  &__nickname {
    font-size: $font-size-lg;
    font-weight: 600;
    color: $color-text-primary;
  }

  &__badge {
    margin-left: $spacing-sm;
    font-size: $font-size-xs;
    color: $color-primary;
  }

  &__uid {
    display: block;
    margin-top: $spacing-xs;
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__vip {
    padding: $spacing-md;
    border-radius: $radius-lg;
    background: linear-gradient(135deg, #fff2cf 0%, #ffe7b3 100%);
  }

  &__vip-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  &__vip-title {
    font-size: $font-size-base;
    font-weight: 700;
    color: #b57500;
  }

  &__vip-more {
    font-size: $font-size-xs;
    color: #b57500;
  }

  &__vip-benefits {
    display: flex;
    justify-content: space-around;
    margin: $spacing-md 0;
  }

  &__vip-benefit {
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  &__vip-benefit-icon {
    width: 64rpx;
    height: 64rpx;
    border-radius: $radius-md;
    background-color: rgba(255, 255, 255, 0.8);
    color: $color-vip;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  &__vip-benefit-label {
    margin-top: 6rpx;
    font-size: $font-size-xs;
    color: #8a6a1a;
  }

  &__vip-btn {
    height: 72rpx;
    border-radius: 36rpx;
    background: linear-gradient(90deg, #ff6b3d 0%, #ff4d4f 100%);
    color: $color-text-inverse;
    font-size: $font-size-base;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  &__body {
    padding: 0 $spacing-lg;
    margin-top: $spacing-md;

    > * {
      margin-bottom: $spacing-md;
    }
  }

  &__footer {
    padding: $spacing-xl 0 $spacing-lg;
    text-align: center;
  }

  &__footer-text {
    font-size: $font-size-xs;
    color: $color-text-placeholder;
  }
}
</style>
