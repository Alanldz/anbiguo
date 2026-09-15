<template>
  <view class="record">
    <view v-if="record" class="record__body">
      <!-- 成绩大卡片 -->
      <view class="record__hero" :class="record.is_passed ? 'is-passed' : 'is-failed'">
        <text class="record__hero-label">{{ record.title }}</text>
        <view class="record__score-wrap">
          <text class="record__score">{{ formatScore(record.score) }}</text>
          <text class="record__score-total">/ {{ record.total_score }}</text>
        </view>
        <view class="record__pass-tag">
          {{ record.is_passed ? '恭喜通过' : '未及格，继续加油' }}
        </view>
      </view>

      <!-- 统计区 -->
      <view class="record__stats">
        <view class="record__stat">
          <text class="record__stat-value">{{ accuracyPercent }}%</text>
          <text class="record__stat-label">正确率</text>
        </view>
        <view class="record__stat">
          <text class="record__stat-value">{{ formatDuration(record.cost_seconds) }}</text>
          <text class="record__stat-label">用时</text>
        </view>
        <view class="record__stat">
          <text class="record__stat-value">{{ record.pass_score }}</text>
          <text class="record__stat-label">及格线</text>
        </view>
      </view>

      <!-- 对错分布 -->
      <view class="record__card">
        <text class="record__card-title">答题分布</text>
        <view class="record__bar">
          <view class="record__bar-segment is-correct" :style="{ width: `${correctPercent}%` }" />
          <view class="record__bar-segment is-wrong" :style="{ width: `${wrongPercent}%` }" />
          <view class="record__bar-segment is-blank" :style="{ width: `${blankPercent}%` }" />
        </view>
        <view class="record__legend">
          <view class="record__legend-item">
            <view class="record__dot is-correct" />
            <text>答对 {{ record.correct_count }}</text>
          </view>
          <view class="record__legend-item">
            <view class="record__dot is-wrong" />
            <text>答错 {{ wrongCount }}</text>
          </view>
          <view class="record__legend-item">
            <view class="record__dot is-blank" />
            <text>未答 {{ blankCount }}</text>
          </view>
        </view>
      </view>

      <!-- 交卷信息 -->
      <view class="record__card">
        <view class="record__meta">
          <text class="record__meta-label">交卷时间</text>
          <text class="record__meta-value">{{ record.created_at }}</text>
        </view>
        <view class="record__meta">
          <text class="record__meta-label">题目总数</text>
          <text class="record__meta-value">{{ record.question_count }} 题</text>
        </view>
        <view class="record__meta">
          <text class="record__meta-label">平均每题</text>
          <text class="record__meta-value">
            {{ record.question_count ? formatDuration(Math.round(record.cost_seconds / record.question_count)) : '-' }}
          </text>
        </view>
      </view>

      <!-- 操作按钮 -->
      <view class="record__actions">
        <view class="record__btn record__btn--plain" @tap="goReview">查看试卷回顾</view>
        <view class="record__btn record__btn--primary" @tap="goRetry">再考一次</view>
      </view>
    </view>

    <view v-else class="record__loading">
      <text class="record__loading-text">成绩加载中…</text>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 成绩单（P-13）
 * 接口：API-EXM-004 成绩详情（GET /exam-records/{id}，含 is_passed / answers 明细）
 * 说明：醒目大数字展示得分，正确率 / 用时 / 及格标签与对错分布；
 *       查看试卷回顾 → exam/review?id=；再考一次 → exam/create。
 */
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { fetchExamRecord } from '@/api/exam'
import type { ExamRecordDetail } from '@/types'

const record = ref<ExamRecordDetail | null>(null)

/** 已答题数（有作答内容的题） */
const answeredCount = computed(
  () => record.value?.answers.filter((item) => item.user_answer && item.user_answer !== '未作答').length ?? 0
)
const wrongCount = computed(() =>
  record.value ? Math.max(0, answeredCount.value - record.value.correct_count) : 0
)
const blankCount = computed(() =>
  record.value ? Math.max(0, record.value.question_count - answeredCount.value) : 0
)

const accuracyPercent = computed(() =>
  record.value && record.value.question_count
    ? Math.round((record.value.correct_count / record.value.question_count) * 100)
    : 0
)
const correctPercent = computed(() =>
  record.value && record.value.question_count
    ? (record.value.correct_count / record.value.question_count) * 100
    : 0
)
const wrongPercent = computed(() =>
  record.value && record.value.question_count ? (wrongCount.value / record.value.question_count) * 100 : 0
)
const blankPercent = computed(() =>
  record.value && record.value.question_count ? (blankCount.value / record.value.question_count) * 100 : 0
)

onLoad(async (options) => {
  const id = Number(options?.id ?? 0)
  if (!id) {
    uni.showToast({ title: '记录不存在', icon: 'none' })
    return
  }
  record.value = await fetchExamRecord(id)
  uni.setNavigationBarTitle({ title: record.value.title || '成绩单' })
})

/** 得分去掉无意义小数（如 87.5 保留、90.0 显示 90） */
function formatScore(score: number): string {
  return Number.isInteger(score) ? String(score) : score.toFixed(1)
}

function formatDuration(seconds: number): string {
  const mm = Math.floor(seconds / 60)
  const ss = seconds % 60
  return mm ? `${mm}分${String(ss).padStart(2, '0')}秒` : `${ss}秒`
}

function goReview() {
  uni.navigateTo({ url: `/pages-sub/exam/review?id=${record.value?.id ?? 0}` })
}

function goRetry() {
  // 回到发起考试页重新组卷
  uni.redirectTo({ url: '/pages-sub/exam/create' })
}
</script>

<style lang="scss" scoped>
.record {
  min-height: 100vh;
  padding: $spacing-lg;
  background-color: $color-bg-page;

  &__hero {
    border-radius: $radius-xl;
    padding: $spacing-xl $spacing-lg;
    display: flex;
    flex-direction: column;
    align-items: center;
    box-shadow: $shadow-lg;

    &.is-passed {
      background: $color-primary-gradient;
    }

    &.is-failed {
      background: linear-gradient(135deg, #f97316 0%, #ef4444 100%);
    }
  }

  &__hero-label {
    font-size: $font-size-base;
    color: $color-text-inverse;
    opacity: 0.9;
  }

  &__score-wrap {
    display: flex;
    align-items: baseline;
    margin: $spacing-sm 0;
  }

  &__score {
    font-size: 120rpx;
    line-height: 1.1;
    font-weight: 700;
    color: $color-text-inverse;
  }

  &__score-total {
    margin-left: $spacing-xs;
    font-size: $font-size-lg;
    color: $color-text-inverse;
    opacity: 0.8;
  }

  &__pass-tag {
    padding: 6rpx 28rpx;
    border-radius: 30rpx;
    background-color: rgba(255, 255, 255, 0.2);
    font-size: $font-size-sm;
    color: $color-text-inverse;
  }

  &__stats {
    display: flex;
    margin-top: $spacing-md;
    padding: $spacing-md 0;
    background-color: $color-bg-card;
    border-radius: $radius-lg;
  }

  &__stat {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  &__stat-value {
    font-size: $font-size-lg;
    font-weight: 700;
    color: $color-text-primary;
  }

  &__stat-label {
    margin-top: 4rpx;
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__card {
    margin-top: $spacing-md;
    padding: $spacing-lg;
    background-color: $color-bg-card;
    border-radius: $radius-lg;
  }

  &__card-title {
    display: block;
    font-size: $font-size-base;
    font-weight: 600;
    color: $color-text-primary;
    margin-bottom: $spacing-md;
  }

  &__bar {
    display: flex;
    height: 24rpx;
    border-radius: 12rpx;
    overflow: hidden;
    background-color: $color-divider;
  }

  &__bar-segment {
    height: 100%;

    &.is-correct {
      background-color: $color-success;
    }

    &.is-wrong {
      background-color: $color-danger;
    }

    &.is-blank {
      background-color: $color-text-placeholder;
    }
  }

  &__legend {
    display: flex;
    justify-content: space-between;
    margin-top: $spacing-md;
  }

  &__legend-item {
    display: flex;
    align-items: center;
    font-size: $font-size-xs;
    color: $color-text-regular;
  }

  &__dot {
    width: 20rpx;
    height: 20rpx;
    border-radius: $radius-circle;
    margin-right: $spacing-xs;

    &.is-correct {
      background-color: $color-success;
    }

    &.is-wrong {
      background-color: $color-danger;
    }

    &.is-blank {
      background-color: $color-text-placeholder;
    }
  }

  &__meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: $spacing-sm 0;

    &:not(:last-child) {
      border-bottom: 1rpx solid $color-divider;
    }
  }

  &__meta-label {
    font-size: $font-size-sm;
    color: $color-text-secondary;
  }

  &__meta-value {
    font-size: $font-size-sm;
    color: $color-text-primary;
  }

  &__actions {
    display: flex;
    margin-top: $spacing-lg;
  }

  &__btn {
    flex: 1;
    height: 88rpx;
    border-radius: 44rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: $font-size-base;

    &--plain {
      margin-right: $spacing-sm;
      border: 2rpx solid $color-primary;
      color: $color-primary;
    }

    &--primary {
      background: $color-primary-gradient;
      color: $color-text-inverse;
      font-weight: 600;
    }
  }

  &__loading {
    padding: $spacing-xl * 2;
    text-align: center;
  }

  &__loading-text {
    font-size: $font-size-sm;
    color: $color-text-placeholder;
  }
}
</style>
