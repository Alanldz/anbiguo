<template>
  <view class="exam-page">
    <!-- 未选择题库：引导态 -->
    <view v-if="!bankStore.currentBank" class="exam-page__empty">
      <text class="exam-page__empty-title">您暂未选择题库</text>
      <text class="exam-page__empty-desc">请通过下方入口选择或导入题库</text>

      <view class="exam-page__entry exam-page__entry--blue" @tap="goBankList">
        <view class="exam-page__entry-main">
          <text class="exam-page__entry-title">选择题库 ›</text>
          <text class="exam-page__entry-desc">驾考、建筑、消防、特种、医考等全题库</text>
        </view>
        <text class="exam-page__entry-icon">✓</text>
      </view>

      <view class="exam-page__entry exam-page__entry--green" @tap="goImport">
        <view class="exam-page__entry-main">
          <text class="exam-page__entry-title">导入题库 ›</text>
          <text class="exam-page__entry-desc">我有题目资料，一键导入生成电子题库</text>
        </view>
        <text class="exam-page__entry-icon">↑</text>
      </view>
    </view>

    <!-- 已选择题库：考试中心 -->
    <view v-else class="exam-page__content">
      <view class="exam-page__current card">
        <view class="exam-page__current-main">
          <text class="exam-page__current-label">当前题库</text>
          <text class="exam-page__current-title text-ellipsis">{{ bankStore.currentBank.title }}</text>
          <text class="exam-page__current-meta">{{ bankStore.currentBank.question_count }} 道题</text>
        </view>
        <text class="exam-page__current-switch" @tap="goBankList">切换</text>
      </view>

      <grid-menu :items="examEntries" :columns="4" @select="handleEntry" />

      <view class="section-title">
        <text class="section-title__text">最近考试记录</text>
        <text class="section-title__more" @tap="goHistory">全部 ›</text>
      </view>

      <view v-for="record in records" :key="record.id" class="exam-page__record card" @tap="goRecord(record.id)">
        <view class="exam-page__record-main">
          <text class="exam-page__record-title text-ellipsis">{{ record.title }}</text>
          <text class="exam-page__record-time">{{ record.created_at }}</text>
        </view>
        <view class="exam-page__record-score">
          <text class="exam-page__record-score-value">{{ record.score }}</text>
          <text class="exam-page__record-score-unit">分</text>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 考试页（P-04）
 * 两种状态：未选择题库（引导）/ 已选择题库（考试中心）
 * 接口：API-EXM-001 发起考试、API-EXM-005 考试记录列表
 */
import { onShow } from '@dcloudio/uni-app'
import { ref } from 'vue'
import { useBankStore } from '@/stores/bank'
import { fetchExamRecords, type ExamRecord } from '@/api/exam'
import type { GridMenuItem } from '@/components/grid-menu/grid-menu.vue'

const bankStore = useBankStore()
const records = ref<ExamRecord[]>([])

const examEntries: GridMenuItem[] = [
  { key: 'mock', label: '模拟考试', iconText: '考', path: '/pages-sub/exam/create' },
  { key: 'random', label: '随机练习', iconText: '随', path: '/pages-sub/practice/answer?mode=random' },
  { key: 'wrong', label: '错题重做', iconText: '错', path: '/pages-sub/wrong/list' },
  { key: 'history', label: '考试记录', iconText: '录', path: '/pages-sub/exam/history' }
]

onShow(async () => {
  const lastBankId = bankStore.getLastBankId()
  if (lastBankId) await bankStore.setCurrentBank(lastBankId)
  const result = await fetchExamRecords({ page: 1, page_size: 5 })
  records.value = result.list
})

function goBankList() {
  uni.switchTab({ url: '/pages/bank/list' })
}

function goImport() {
  uni.navigateTo({ url: '/pages-sub/import/upload' })
}

function goHistory() {
  uni.navigateTo({ url: '/pages-sub/exam/history' })
}

function goRecord(id: number) {
  uni.navigateTo({ url: `/pages-sub/exam/record?id=${id}` })
}

function handleEntry(item: GridMenuItem) {
  if (item.path) {
    uni.navigateTo({ url: item.path })
    return
  }
  uni.showToast({ title: `${item.label} 开发中`, icon: 'none' })
}
</script>

<style lang="scss" scoped>
.exam-page {
  min-height: 100vh;
  padding: $spacing-lg;
  background-color: $color-bg-page;

  &__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 160rpx;
  }

  &__empty-title {
    font-size: $font-size-xl;
    font-weight: 600;
    color: $color-text-primary;
  }

  &__empty-desc {
    margin-top: $spacing-sm;
    font-size: $font-size-sm;
    color: $color-text-secondary;
  }

  &__entry {
    width: 100%;
    margin-top: $spacing-xl;
    padding: $spacing-lg;
    border-radius: $radius-xl;
    display: flex;
    align-items: center;
    justify-content: space-between;

    &--blue {
      background: linear-gradient(135deg, #3d86ff 0%, #1f6bff 100%);
    }

    &--green {
      background: linear-gradient(135deg, #34d07f 0%, #16a34a 100%);
    }
  }

  &__entry-title {
    display: block;
    font-size: $font-size-lg;
    font-weight: 600;
    color: $color-text-inverse;
  }

  &__entry-desc {
    display: block;
    margin-top: $spacing-xs;
    font-size: $font-size-sm;
    color: rgba(255, 255, 255, 0.85);
  }

  &__entry-icon {
    font-size: 64rpx;
    color: rgba(255, 255, 255, 0.5);
  }

  &__content {
    > * {
      margin-bottom: $spacing-md;
    }
  }

  &__current {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  &__current-label {
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__current-title {
    display: block;
    margin-top: 4rpx;
    font-size: $font-size-md;
    font-weight: 600;
    color: $color-text-primary;
  }

  &__current-meta {
    display: block;
    margin-top: 4rpx;
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__current-switch {
    font-size: $font-size-sm;
    color: $color-primary;
  }

  &__record {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  &__record-main {
    flex: 1;
    min-width: 0;
  }

  &__record-title {
    font-size: $font-size-base;
    color: $color-text-primary;
  }

  &__record-time {
    display: block;
    margin-top: 4rpx;
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__record-score {
    display: flex;
    align-items: baseline;
  }

  &__record-score-value {
    font-size: $font-size-xl;
    font-weight: 700;
    color: $color-primary;
  }

  &__record-score-unit {
    margin-left: 4rpx;
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }
}
</style>
