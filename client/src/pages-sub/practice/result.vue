<template>
  <view class="result">
    <!-- 结果大卡片 -->
    <view class="result__hero">
      <text class="result__hero-label">练习完成</text>
      <view class="result__ring" :class="isGood ? 'is-good' : 'is-bad'">
        <text class="result__ring-value">{{ ratePercent }}%</text>
      </view>
      <view class="result__rate-row">
        <text class="result__rate-text">答对 {{ correct }} 题 / 共 {{ total }} 题</text>
      </view>
      <view v-if="score !== null" class="result__score-row">
        <text class="result__score">得分 {{ score }}</text>
      </view>
    </view>

    <!-- 统计 -->
    <view class="result__stats">
      <view class="result__stat">
        <text class="result__stat-value">{{ total }}</text>
        <text class="result__stat-label">答题数</text>
      </view>
      <view class="result__stat">
        <text class="result__stat-value">{{ correct }}</text>
        <text class="result__stat-label">答对</text>
      </view>
      <view class="result__stat">
        <text class="result__stat-value">{{ wrongCount }}</text>
        <text class="result__stat-label">答错</text>
      </view>
      <view class="result__stat">
        <text class="result__stat-value">{{ durationText }}</text>
        <text class="result__stat-label">用时</text>
      </view>
    </view>

    <!-- 鼓励文案 -->
    <view class="result__tip-card">
      <text class="result__tip">
        {{ tipText }}
      </text>
    </view>

    <!-- 操作按钮 -->
    <view class="result__actions">
      <view class="result__btn result__btn--plain" @tap="goBack">返回题库</view>
      <view class="result__btn result__btn--primary" @tap="goRetry">再练一次</view>
    </view>
    <view class="result__link" @tap="goWrong">
      查看错题本 ›
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 练习结果（P-10）
 * 数据来源：练习/考试结束参数（由 practice/answer 页跳转携带 onLoad 参数：
 *   correct 答对数 / total 总题数 / seconds 用时秒 / score 得分(可选) / bank_id / mode）
 * 说明：大数字展示正确率与答对数；按钮：返回题库 / 再练一次 / 查看错题。
 */
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'

const correct = ref(0)
const total = ref(0)
const seconds = ref(0)
const score = ref<number | null>(null)
const bankId = ref(0)
const mode = ref('sequence')

const ratePercent = computed(() => (total.value ? Math.round((correct.value / total.value) * 100) : 0))
const wrongCount = computed(() => Math.max(0, total.value - correct.value))
/** 正确率 ≥ 80% 视为发挥良好，绿色主题 */
const isGood = computed(() => ratePercent.value >= 80)

const durationText = computed(() => {
  const mm = Math.floor(seconds.value / 60)
  const ss = seconds.value % 60
  return mm ? `${mm}分${String(ss).padStart(2, '0')}秒` : `${ss}秒`
})

const tipText = computed(() => {
  if (!total.value) return '本次练习没有作答记录，再来一轮试试吧。'
  if (ratePercent.value >= 90) return '太厉害了！正确率超过 90%，可以挑战更难的题目了。'
  if (ratePercent.value >= 80) return '发挥不错！错题已自动加入错题本，记得及时复习。'
  if (ratePercent.value >= 60) return '还有提升空间，建议先回顾错题解析再重练一轮。'
  return '别灰心，多练几轮、吃透解析，正确率会稳步提升的。'
})

onLoad((options) => {
  correct.value = Number(options?.correct ?? 0)
  total.value = Number(options?.total ?? 0)
  seconds.value = Number(options?.seconds ?? 0)
  score.value = options?.score !== undefined && options?.score !== '' ? Number(options.score) : null
  bankId.value = Number(options?.bank_id ?? 0)
  mode.value = options?.mode ?? 'sequence'
  uni.setNavigationBarTitle({ title: '练习结果' })
})

function goBack() {
  // 有题库上下文则回到题库详情，否则逐层返回
  if (bankId.value) {
    uni.redirectTo({ url: `/pages-sub/bank/detail?id=${bankId.value}` })
  } else {
    uni.navigateBack()
  }
}

function goRetry() {
  if (!bankId.value) {
    uni.showToast({ title: '缺少题库信息', icon: 'none' })
    return
  }
  uni.redirectTo({
    url: `/pages-sub/practice/answer?bank_id=${bankId.value}&mode=${mode.value}`
  })
}

function goWrong() {
  uni.navigateTo({
    url: bankId.value ? `/pages-sub/wrong/list?bank_id=${bankId.value}` : '/pages-sub/wrong/list'
  })
}
</script>

<style lang="scss" scoped>
.result {
  min-height: 100vh;
  padding: $spacing-lg;
  background-color: $color-bg-page;

  &__hero {
    padding: $spacing-xl $spacing-lg;
    background-color: $color-bg-card;
    border-radius: $radius-xl;
    display: flex;
    flex-direction: column;
    align-items: center;
    box-shadow: $shadow-sm;
  }

  &__hero-label {
    font-size: $font-size-base;
    color: $color-text-secondary;
  }

  &__ring {
    width: 240rpx;
    height: 240rpx;
    margin: $spacing-lg 0;
    border-radius: $radius-circle;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 16rpx solid $color-success;
    background-color: $color-success-bg;

    &.is-good {
      border-color: $color-success;
      background-color: $color-success-bg;
    }

    &.is-bad {
      border-color: $color-warning;
      background-color: $color-warning-bg;
    }
  }

  &__ring-value {
    font-size: $font-size-xxl;
    font-weight: 700;
    color: $color-text-primary;
  }

  &__rate-row {
    margin-top: $spacing-xs;
  }

  &__rate-text {
    font-size: $font-size-sm;
    color: $color-text-regular;
  }

  &__score-row {
    margin-top: $spacing-sm;
  }

  &__score {
    font-size: $font-size-lg;
    font-weight: 700;
    color: $color-primary;
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

  &__tip-card {
    margin-top: $spacing-md;
    padding: $spacing-md $spacing-lg;
    background-color: $color-primary-bg;
    border-radius: $radius-lg;
  }

  &__tip {
    font-size: $font-size-sm;
    line-height: 1.7;
    color: $color-primary;
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
      border: 2rpx solid $color-border;
      color: $color-text-regular;
    }

    &--primary {
      background: $color-primary-gradient;
      color: $color-text-inverse;
      font-weight: 600;
    }
  }

  &__link {
    margin-top: $spacing-lg;
    text-align: center;
    font-size: $font-size-sm;
    color: $color-primary;
  }
}
</style>
