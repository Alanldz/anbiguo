<template>
  <view class="history">
    <view class="history__list">
      <template v-if="list.length">
        <view
          v-for="item in list"
          :key="item.id"
          class="history__item"
          @tap="goRecord(item.id)"
        >
          <view class="history__item-header">
            <text class="history__title text-ellipsis">{{ item.title }}</text>
            <text class="history__tag" :class="isPassed(item) ? 'is-passed' : 'is-failed'">
              {{ isPassed(item) ? '及格' : '不及格' }}
            </text>
          </view>
          <view class="history__score-row">
            <view class="history__score">
              <text class="history__score-value">{{ formatScore(item.score) }}</text>
              <text class="history__score-total">/ {{ item.total_score }}</text>
            </view>
            <view class="history__metrics">
              <text class="history__metric">正确率 {{ accuracy(item) }}%</text>
              <text class="history__metric">{{ item.question_count }} 题</text>
              <text class="history__metric">用时 {{ formatDuration(item.cost_seconds) }}</text>
            </view>
          </view>
          <view class="history__item-footer">
            <text class="history__time">{{ item.created_at }}</text>
            <text class="history__link">查看成绩单 ›</text>
          </view>
        </view>
      </template>

      <base-empty
        v-else-if="!loading"
        title="还没有考试记录"
        desc="去「发起考试」组一张模拟卷，检验学习成果吧"
        icon-text="考"
      />

      <list-load-more :loading="loading" :has-more="hasMore" />
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 考试记录列表（P-15）
 * 接口：API-EXM-005 考试记录列表（GET /exam-records，分页）
 * 说明：按时间倒序分页卡片（试卷名、题数、正确率、得分、及格标签、时间），
 *       点击进入成绩单 exam/record?id=；空状态用 base-empty。
 */
import { onMounted, ref } from 'vue'
import { onReachBottom } from '@dcloudio/uni-app'
import { fetchExamRecords } from '@/api/exam'
import type { ExamRecord } from '@/types'

const list = ref<ExamRecord[]>([])
const loading = ref(false)
const hasMore = ref(true)
const page = ref(1)
const pageSize = 20

/** 及格线：总分的 60%（列表接口不含 pass_score，按统一口径前端计算） */
function isPassed(item: ExamRecord): boolean {
  return item.score >= item.total_score * 0.6
}

function accuracy(item: ExamRecord): number {
  return item.question_count ? Math.round((item.correct_count / item.question_count) * 100) : 0
}

function formatScore(score: number): string {
  return Number.isInteger(score) ? String(score) : score.toFixed(1)
}

function formatDuration(seconds: number): string {
  const mm = Math.floor(seconds / 60)
  const ss = seconds % 60
  return mm ? `${mm}分${String(ss).padStart(2, '0')}秒` : `${ss}秒`
}

onMounted(() => loadData(true))

async function loadData(reset = false) {
  if (reset) {
    page.value = 1
    hasMore.value = true
    list.value = []
  }
  if (loading.value || (!reset && !hasMore.value)) return
  loading.value = true
  try {
    const res = await fetchExamRecords({ page: page.value, page_size: pageSize })
    list.value = reset ? res.list : [...list.value, ...res.list]
    page.value += 1
    hasMore.value = res.pagination.page < res.pagination.total_pages
  } finally {
    loading.value = false
  }
}

onReachBottom(() => loadData(false))

function goRecord(id: number) {
  uni.navigateTo({ url: `/pages-sub/exam/record?id=${id}` })
}
</script>

<style lang="scss" scoped>
.history {
  min-height: 100vh;
  background-color: $color-bg-page;

  &__list {
    padding: $spacing-lg;
  }

  &__item {
    padding: $spacing-md;
    margin-bottom: $spacing-sm;
    background-color: $color-bg-card;
    border-radius: $radius-lg;
    box-shadow: $shadow-sm;
  }

  &__item-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: $spacing-sm;
  }

  &__title {
    flex: 1;
    margin-right: $spacing-sm;
    font-size: $font-size-base;
    font-weight: 600;
    color: $color-text-primary;
  }

  &__tag {
    flex-shrink: 0;
    font-size: $font-size-xs;
    padding: 2rpx 12rpx;
    border-radius: $radius-sm;

    &.is-passed {
      color: $color-success;
      background-color: $color-success-bg;
    }

    &.is-failed {
      color: $color-danger;
      background-color: $color-danger-bg;
    }
  }

  &__score-row {
    display: flex;
    align-items: center;
  }

  &__score {
    display: flex;
    align-items: baseline;
    min-width: 180rpx;
  }

  &__score-value {
    font-size: $font-size-xxl;
    font-weight: 700;
    color: $color-primary;
  }

  &__score-total {
    margin-left: 4rpx;
    font-size: $font-size-xs;
    color: $color-text-placeholder;
  }

  &__metrics {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
  }

  &__metric {
    font-size: $font-size-xs;
    line-height: 1.8;
    color: $color-text-secondary;
  }

  &__item-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: $spacing-sm;
    padding-top: $spacing-sm;
    border-top: 1rpx solid $color-divider;
  }

  &__time {
    font-size: $font-size-xs;
    color: $color-text-placeholder;
  }

  &__link {
    font-size: $font-size-xs;
    color: $color-primary;
  }
}
</style>
