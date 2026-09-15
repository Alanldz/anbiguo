<template>
  <view class="detail">
    <nav-bar :title="bank?.title ?? '题库详情'" theme="light">
      <template #right>
        <text class="detail__nav-icon" @tap="handleMore">⋯</text>
      </template>
    </nav-bar>

    <view class="detail__hero">
      <view class="detail__bank">
        <text class="detail__bank-title">{{ bank?.title }}</text>
        <view class="detail__bank-meta">
          <text class="detail__bank-date">{{ bank?.created_at }}</text>
          <text class="detail__bank-count">{{ bank?.question_count }} 题</text>
          <text class="detail__bank-source">{{ sourceLabel }}</text>
        </view>
      </view>

      <!-- 顶部快捷 4 项 -->
      <view class="detail__quick">
        <view v-for="item in quickEntries" :key="item.key" class="detail__quick-item" @tap="handleEntry(item)">
          <text class="detail__quick-icon">{{ item.iconText }}</text>
          <text class="detail__quick-label">{{ item.label }}</text>
        </view>
      </view>
    </view>

    <view class="detail__body">
      <!-- 主功能区 -->
      <view class="detail__actions">
        <view class="detail__action-main" @tap="goPractice('sequence')">
          <text class="detail__action-main-title">顺序练习</text>
          <text class="detail__action-main-progress">{{ practicedText }}</text>
        </view>
        <view class="detail__action-main detail__action-main--exam" @tap="goExam">
          <text class="detail__action-main-title">模拟考试</text>
          <text class="detail__action-main-progress">去考试</text>
        </view>
      </view>

      <grid-menu :items="functionEntries" :columns="4" @select="handleEntry" />

      <!-- 运营位 -->
      <view class="detail__promo">
        <text class="detail__promo-title">错题整理 就用识途</text>
        <text class="detail__promo-desc">错题举一反三，薄弱点更清楚</text>
        <text class="detail__promo-btn" @tap="goWrong">立即使用</text>
      </view>

      <!-- 学习资料 -->
      <view class="section-title">
        <text class="section-title__text">学习资料</text>
        <text class="section-title__more" @tap="goResource">上传资料 ›</text>
      </view>
      <view class="detail__resource">
        <text class="detail__resource-empty">可上传文档 / 讲义 / 音视频</text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 题库详情页（P-07）
 * 结构：题库信息 + 快捷入口 + 功能宫格 + 学习资料
 * 接口：API-BANK-003 题库详情、API-FIL-003 学习资料列表
 */
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { useBankStore } from '@/stores/bank'
import { BankSourceType } from '@/types'
import type { GridMenuItem } from '@/components/grid-menu/grid-menu.vue'

const bankStore = useBankStore()
const bankId = ref(0)

const bank = computed(() => bankStore.currentBank)

const SOURCE_LABEL: Record<number, string> = {
  [BankSourceType.Upload]: '上传',
  [BankSourceType.Official]: '官方',
  [BankSourceType.Purchased]: '购买',
  [BankSourceType.AiGenerated]: 'AI生成'
}
const sourceLabel = computed(() => (bank.value ? SOURCE_LABEL[bank.value.source_type] : ''))

const practicedText = computed(() => {
  if (!bank.value) return ''
  return `${bank.value.practiced_count}/${bank.value.question_count}`
})

const quickEntries = [
  { key: 'wrong', label: '错题·收藏', iconText: '错', path: '/pages-sub/wrong/list' },
  { key: 'cut', label: '我的斩题', iconText: '斩', path: '' },
  { key: 'note', label: '我的笔记', iconText: '记', path: '/pages-sub/note/list' },
  { key: 'share', label: '分享赚钱', iconText: '享', path: '' }
]

const functionEntries: GridMenuItem[] = [
  { key: 'vip', label: '会员中心', iconText: 'V', color: '#F7B500', bgColor: '#FFF8E6', path: '/pages-sub/member/index' },
  { key: 'simple', label: '精简题', iconText: '简', path: '' },
  { key: 'chapter', label: '专项练习', iconText: '专', path: '/pages-sub/practice/answer?mode=chapter' },
  { key: 'easy-wrong', label: '易错题', iconText: '易', path: '' },
  { key: 'flash', label: '试题闪卡', iconText: '闪', path: '' },
  { key: 'points', label: '考点速记', iconText: '点', path: '' },
  { key: 'offline', label: '离线练习', iconText: '离', path: '' },
  { key: 'search', label: '搜索试题', iconText: '搜', path: '/pages-sub/search/index?mode=bank' }
]

onLoad(async (options) => {
  bankId.value = Number(options?.id ?? 0)
  if (bankId.value) await bankStore.setCurrentBank(bankId.value)
})

function goPractice(mode: string) {
  uni.navigateTo({ url: `/pages-sub/practice/answer?bank_id=${bankId.value}&mode=${mode}` })
}

function goExam() {
  uni.navigateTo({ url: `/pages-sub/exam/create?bank_id=${bankId.value}` })
}

function goWrong() {
  uni.navigateTo({ url: `/pages-sub/wrong/list?bank_id=${bankId.value}` })
}

function goResource() {
  uni.navigateTo({ url: `/pages-sub/resource/list?bank_id=${bankId.value}` })
}

function handleEntry(item: { key?: string; label: string; path?: string }) {
  // 易错题集需要携带当前题库 id
  if (item.key === 'easy-wrong') {
    uni.navigateTo({ url: `/pages-sub/bank/error-prone?bank_id=${bankId.value}` })
    return
  }
  if (item.path) {
    uni.navigateTo({ url: item.path })
    return
  }
  // 精简题/试题闪卡/考点速记/离线练习等规划中的功能
  uni.showToast({ title: '该功能规划中，敬请期待', icon: 'none' })
}

/** 右上角「⋯」：重命名（API-BANK-005）/ 删除（API-BANK-006）；分享与导出暂无客户端接口 */
function handleMore() {
  uni.showActionSheet({
    itemList: ['重命名题库', '分享题库', '导出题库', '删除题库'],
    success: ({ tapIndex }) => {
      if (tapIndex === 0) handleRename()
      else if (tapIndex === 1) uni.showToast({ title: '分享功能待上线', icon: 'none' })
      else if (tapIndex === 2) uni.showToast({ title: '导出功能待上线', icon: 'none' })
      else handleDelete()
    }
  })
}

/** API-BANK-005 更新题库名：弹输入框 → 调接口 → 刷新 store 与页面标题 */
function handleRename() {
  uni.showModal({
    title: '重命名题库',
    editable: true,
    placeholderText: '请输入新的题库名称',
    content: bank.value?.title ?? '',
    success: async (modal) => {
      const title = (modal.content ?? '').trim()
      if (!modal.confirm || !title) return
      await updateBank(bankId.value, { title })
      // 刷新 store 与页面标题
      if (bank.value) {
        bankStore.currentBank = { ...bank.value, title }
      }
      uni.setNavigationBarTitle({ title })
      uni.showToast({ title: '重命名成功', icon: 'none' })
    }
  })
}

/** API-BANK-006 删除题库：二次确认 → 软删 → 返回题库列表页 */
function handleDelete() {
  uni.showModal({
    title: '删除题库',
    content: '删除后题库进入回收站，确定删除吗？',
    confirmColor: '#EF4444',
    success: async (modal) => {
      if (!modal.confirm) return
      await deleteBank(bankId.value)
      bankStore.currentBank = null
      bankStore.banks = bankStore.banks.filter((item) => item.id !== bankId.value)
      uni.showToast({ title: '已删除', icon: 'none' })
      setTimeout(() => uni.navigateBack(), 500)
    }
  })
}
</script>

<style lang="scss" scoped>
.detail {
  min-height: 100vh;
  background-color: $color-bg-page;
  padding-bottom: $spacing-xl;

  &__nav-icon {
    font-size: $font-size-lg;
    color: $color-text-regular;
  }

  &__hero {
    background: $color-primary-gradient;
    padding: $spacing-lg;
    border-radius: 0 0 $radius-xl $radius-xl;
  }

  &__bank-title {
    display: block;
    font-size: $font-size-xl;
    font-weight: 700;
    color: $color-text-inverse;
  }

  &__bank-meta {
    display: flex;
    align-items: center;
    margin-top: $spacing-sm;
  }

  &__bank-date,
  &__bank-count,
  &__bank-source {
    font-size: $font-size-xs;
    color: rgba(255, 255, 255, 0.85);
    margin-right: $spacing-md;
  }

  &__quick {
    display: flex;
    margin-top: $spacing-lg;
  }

  &__quick-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  &__quick-icon {
    width: 72rpx;
    height: 72rpx;
    border-radius: $radius-lg;
    background-color: rgba(255, 255, 255, 0.22);
    color: $color-text-inverse;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: $font-size-sm;
  }

  &__quick-label {
    margin-top: $spacing-xs;
    font-size: $font-size-xs;
    color: rgba(255, 255, 255, 0.9);
  }

  &__body {
    padding: 0 $spacing-lg;
    margin-top: $spacing-md;

    > * {
      margin-bottom: $spacing-md;
    }
  }

  &__actions {
    display: flex;
  }

  &__action-main {
    flex: 1;
    margin-right: $spacing-sm;
    padding: $spacing-lg;
    border-radius: $radius-lg;
    background: linear-gradient(135deg, #e8fbef 0%, #d6f5e4 100%);

    &--exam {
      margin-right: 0;
      background: linear-gradient(135deg, #eaf2ff 0%, #dbe9ff 100%);
    }
  }

  &__action-main-title {
    display: block;
    font-size: $font-size-md;
    font-weight: 600;
    color: $color-text-primary;
  }

  &__action-main-progress {
    display: block;
    margin-top: $spacing-sm;
    font-size: $font-size-sm;
    color: $color-text-secondary;
  }

  &__promo {
    padding: $spacing-md;
    border-radius: $radius-lg;
    background: linear-gradient(90deg, #fff6e6 0%, #ffeede 100%);
    position: relative;
  }

  &__promo-title {
    display: block;
    font-size: $font-size-base;
    font-weight: 600;
    color: #b5651d;
  }

  &__promo-desc {
    display: block;
    margin-top: 4rpx;
    font-size: $font-size-xs;
    color: #a97b4a;
  }

  &__promo-btn {
    position: absolute;
    right: $spacing-md;
    top: 50%;
    transform: translateY(-50%);
    font-size: $font-size-xs;
    color: $color-text-inverse;
    background-color: #ff7a45;
    padding: 10rpx 24rpx;
    border-radius: 28rpx;
  }

  &__resource {
    padding: $spacing-xl 0;
    border-radius: $radius-lg;
    background-color: $color-bg-card;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  &__resource-empty {
    font-size: $font-size-sm;
    color: $color-text-placeholder;
  }
}
</style>
