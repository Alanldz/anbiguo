<template>
  <view class="home">
    <!-- 顶部蓝色渐变区 -->
    <view class="home__header" :style="{ paddingTop: `${statusBarHeight}px` }">
      <view class="home__search-row">
        <view class="home__search" @tap="goSearch">
          <text class="home__search-icon">搜</text>
          <text class="home__search-placeholder">搜索题库、文档、作者</text>
        </view>
        <view class="home__bell" @tap="goMessage">
          <text class="home__bell-text">铃</text>
          <view v-if="hasUnread" class="home__bell-dot" />
        </view>
      </view>

      <!-- 四大快捷入口 -->
      <view class="home__quick">
        <view v-for="entry in quickEntries" :key="entry.key" class="home__quick-item" @tap="handleQuick(entry)">
          <text class="home__quick-icon">{{ entry.iconText }}</text>
          <text class="home__quick-label">{{ entry.label }}</text>
        </view>
      </view>
    </view>

    <view class="home__body">
      <!-- 二级入口 -->
      <grid-menu :items="secondaryEntries" :columns="5" @select="handleGridSelect" />

      <!-- 大家都在练 -->
      <view class="section-title">
        <text class="section-title__text">大家都在练</text>
        <text class="section-title__more" @tap="goMarket">更多 ›</text>
      </view>
      <scroll-view scroll-x class="home__banner-scroll" show-scrollbar="false">
        <view class="home__banner-list">
          <view v-for="item in recommendList" :key="item.id" class="home__banner" @tap="goBankDetail(item.id)">
            <view class="home__banner-tag">{{ item.tag }}</view>
            <text class="home__banner-title">{{ item.title }}</text>
            <text class="home__banner-desc">{{ item.desc }}</text>
            <view class="home__banner-btn">{{ item.buttonText }}</view>
          </view>
        </view>
      </scroll-view>

      <!-- 上传引导 -->
      <view class="home__promo">
        <view class="home__promo-main">
          <text class="home__promo-title">音视频课程支持上传了！</text>
          <text class="home__promo-desc">多类型学习资料打包合集，知识变现更简单</text>
        </view>
        <view class="home__promo-btn" @tap="goAi">去上传售卖</view>
      </view>

      <!-- 我的学习空间 -->
      <view class="section-title">
        <text class="section-title__text">我的学习空间</text>
        <view class="home__space-links">
          <text class="section-title__more" @tap="goConsole">内容管理</text>
          <text class="home__space-divider">|</text>
          <text class="section-title__more" @tap="goPracticeRecord">历史记录</text>
        </view>
      </view>

      <template v-if="bankStore.banks.length">
        <bank-card
          v-for="bank in bankStore.recentBanks"
          :key="bank.id"
          :bank="bank"
          @tap="goBankDetail(bank.id)"
        />
      </template>
      <base-empty
        v-else-if="!bankStore.loading"
        title="还没有题库"
        desc="上传你的题目资料，一键生成电子题库开始刷题"
        icon-text="库"
      >
        <view class="home__empty-btn" @tap="goAi">去导入题库</view>
      </base-empty>
      <list-load-more :loading="bankStore.loading" :has-more="false" :show-no-more="false" />
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 首页（P-01）
 * 结构：搜索 + 快捷入口 + 二级入口 + 运营推荐位 + 我的学习空间
 * 接口：API-BANK-002 我的题库列表、API-USER-003 学习统计
 */
import { onLoad, onPullDownRefresh } from '@dcloudio/uni-app'
import { ref } from 'vue'
import { useBankStore } from '@/stores/bank'
import { useUserStore } from '@/stores/user'
import { getStatusBarHeight } from '@/utils/platform'
import type { GridMenuItem } from '@/components/grid-menu/grid-menu.vue'

const bankStore = useBankStore()
const userStore = useUserStore()

const statusBarHeight = ref(getStatusBarHeight())
const hasUnread = ref(true)

/** 四大快捷入口 */
const quickEntries = [
  { key: 'photo', label: '拍照搜题', iconText: '拍', path: '/pages-sub/search/index?mode=photo' },
  { key: 'text', label: '文字搜题', iconText: '文', path: '/pages-sub/search/index?mode=text' },
  { key: 'upload', label: '上传题库', iconText: '传', path: '/pages-sub/import/upload' },
  { key: 'exam', label: '发起考试', iconText: '考', path: '/pages-sub/exam/create' }
]

/** 二级入口 */
const secondaryEntries: GridMenuItem[] = [
  { key: 'float', label: '悬浮窗搜题', iconText: '悬', color: '#EF4444', bgColor: '#FDECEC' },
  { key: 'market', label: '题库市场', iconText: '市', color: '#F59E0B', bgColor: '#FFF5E6', path: '/pages-sub/bank/market' },
  { key: 'vip', label: '会员中心', iconText: 'V', color: '#F7B500', bgColor: '#FFF8E6', path: '/pages-sub/member/index' },
  { key: 'manual', label: '人工导题', iconText: '人', color: '#22C55E', bgColor: '#E8F8EE' },
  { key: 'ai', label: 'AI出题', iconText: 'AI', color: '#8B5CF6', bgColor: '#F3EEFE', path: '/pages/ai/index' }
]

/** 运营推荐位（后续由管理后台 Banner 配置驱动，见 API-ADM-OPR-001） */
const recommendList = [
  { id: 2001, tag: '口碑爆款', title: '职业选择八步法', desc: '系统解决职业选择难题', buttonText: '立即查看' },
  { id: 2002, tag: '简历优化', title: '1V1 简历深度优化', desc: '助力更快更稳拿 Offer', buttonText: '￥299 起' },
  { id: 2003, tag: '限时', title: '建造师一级冲刺', desc: '打牢基础，稳步上岸', buttonText: '免费试听' }
]

onLoad(() => {
  bankStore.loadCategories()
  bankStore.loadMyBanks(true)
  userStore.fetchProfileIfLogged()
})

onPullDownRefresh(async () => {
  await bankStore.loadMyBanks(true)
  uni.stopPullDownRefresh()
})

function handleQuick(entry: { path: string }) {
  uni.navigateTo({ url: entry.path })
}

function handleGridSelect(item: GridMenuItem) {
  if (!item.path) {
    uni.showToast({ title: `${item.label} 开发中`, icon: 'none' })
  }
}

function goSearch() {
  uni.navigateTo({ url: '/pages-sub/search/index' })
}

function goMarket() {
  uni.navigateTo({ url: '/pages-sub/bank/market' })
}

function goBankDetail(id: number) {
  uni.navigateTo({ url: `/pages-sub/bank/detail?id=${id}` })
}

function goAi() {
  uni.switchTab({ url: '/pages/ai/index' })
}

function goMessage() {
  uni.showToast({ title: '消息中心开发中', icon: 'none' })
}

function goConsole() {
  uni.showToast({ title: '电脑端后台：console 站点，开发中', icon: 'none' })
}

function goPracticeRecord() {
  uni.navigateTo({ url: '/pages-sub/record/list' })
}
</script>

<style lang="scss" scoped>
.home {
  min-height: 100vh;
  padding-bottom: $spacing-xl;

  &__header {
    background: $color-primary-gradient;
    padding: 0 $spacing-lg $spacing-xl;
    border-radius: 0 0 $radius-xl $radius-xl;
  }

  &__search-row {
    display: flex;
    align-items: center;
    padding: $spacing-sm 0 $spacing-lg;
  }

  &__search {
    flex: 1;
    height: 72rpx;
    background-color: rgba(255, 255, 255, 0.92);
    border-radius: 36rpx;
    display: flex;
    align-items: center;
    padding: 0 $spacing-md;
  }

  &__search-icon {
    font-size: $font-size-sm;
    color: $color-text-secondary;
    margin-right: $spacing-xs;
  }

  &__search-placeholder {
    font-size: $font-size-sm;
    color: $color-text-placeholder;
  }

  &__bell {
    position: relative;
    width: 72rpx;
    height: 72rpx;
    margin-left: $spacing-md;
    border-radius: $radius-circle;
    background-color: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  &__bell-text {
    font-size: $font-size-sm;
    color: $color-text-inverse;
  }

  &__bell-dot {
    position: absolute;
    top: 12rpx;
    right: 12rpx;
    width: 16rpx;
    height: 16rpx;
    border-radius: $radius-circle;
    background-color: $color-danger;
  }

  &__quick {
    display: flex;
    background-color: $color-bg-card;
    border-radius: $radius-lg;
    padding: $spacing-lg 0;
    box-shadow: $shadow-lg;
  }

  &__quick-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  &__quick-icon {
    width: 80rpx;
    height: 80rpx;
    border-radius: $radius-lg;
    background-color: $color-primary-bg;
    color: $color-primary;
    font-size: $font-size-md;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  &__quick-label {
    margin-top: $spacing-xs;
    font-size: $font-size-sm;
    color: $color-text-regular;
  }

  &__body {
    padding: 0 $spacing-lg;
    margin-top: $spacing-md;

    > * {
      margin-bottom: $spacing-md;
    }
  }

  &__banner-scroll {
    white-space: nowrap;
  }

  &__banner-list {
    display: flex;
  }

  &__banner {
    width: 420rpx;
    flex-shrink: 0;
    margin-right: $spacing-md;
    padding: $spacing-md;
    border-radius: $radius-lg;
    background: linear-gradient(135deg, #fff4f0 0%, #ffe9e2 100%);
    position: relative;
  }

  &__banner-tag {
    display: inline-block;
    font-size: $font-size-xs;
    color: $color-danger;
    background-color: rgba(255, 255, 255, 0.9);
    padding: 2rpx 10rpx;
    border-radius: $radius-sm;
  }

  &__banner-title {
    display: block;
    margin-top: $spacing-xs;
    font-size: $font-size-md;
    font-weight: 600;
    color: $color-text-primary;
  }

  &__banner-desc {
    display: block;
    margin-top: $spacing-xs;
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__banner-btn {
    display: inline-block;
    margin-top: $spacing-md;
    font-size: $font-size-xs;
    color: $color-text-inverse;
    background-color: $color-primary;
    padding: 8rpx 20rpx;
    border-radius: 24rpx;
  }

  &__promo {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: $spacing-md;
    border-radius: $radius-lg;
    background: linear-gradient(90deg, #3d86ff 0%, #1f6bff 100%);
  }

  &__promo-title {
    display: block;
    font-size: $font-size-base;
    font-weight: 600;
    color: $color-text-inverse;
  }

  &__promo-desc {
    display: block;
    margin-top: 4rpx;
    font-size: $font-size-xs;
    color: rgba(255, 255, 255, 0.85);
  }

  &__promo-btn {
    flex-shrink: 0;
    font-size: $font-size-sm;
    color: $color-primary;
    background-color: $color-text-inverse;
    padding: 10rpx 24rpx;
    border-radius: 28rpx;
  }

  &__space-links {
    display: flex;
    align-items: center;
  }

  &__space-divider {
    margin: 0 $spacing-sm;
    color: $color-border;
    font-size: $font-size-sm;
  }

  &__empty-btn {
    margin-top: $spacing-lg;
    padding: 16rpx 48rpx;
    background-color: $color-primary;
    color: $color-text-inverse;
    font-size: $font-size-sm;
    border-radius: 36rpx;
  }
}
</style>
