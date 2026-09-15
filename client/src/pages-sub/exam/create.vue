<template>
  <view class="create">
    <view class="create__card">
      <!-- 题库选择 -->
      <view class="create__field">
        <text class="create__label">选择题库</text>
        <picker
          class="create__picker"
          :range="bankOptions"
          range-key="title"
          @change="onBankChange"
        >
          <view class="create__picker-inner">
            <text class="create__picker-text">{{ currentBankTitle }}</text>
            <text class="create__picker-arrow">▾</text>
          </view>
        </picker>
      </view>

      <!-- 题数 -->
      <view class="create__field">
        <view class="create__field-head">
          <text class="create__label">题目数量</text>
          <text class="create__field-value">{{ questionCount }} 题</text>
        </view>
        <slider
          class="create__slider"
          :min="10"
          :max="maxCount"
          :step="10"
          :value="questionCount"
          activeColor="#2B7CFF"
          block-size="24"
          @changing="onCountChanging"
          @change="onCountChange"
        />
        <view class="create__slider-ticks">
          <text>10</text>
          <text>{{ maxCount }}</text>
        </view>
      </view>

      <!-- 时长 -->
      <view class="create__field">
        <text class="create__label">考试时长</text>
        <view class="create__durations">
          <view
            v-for="item in DURATIONS"
            :key="item"
            class="create__duration"
            :class="{ 'is-active': durationMinutes === item }"
            @tap="durationMinutes = item"
          >
            <text>{{ item }} 分钟</text>
          </view>
        </view>
      </view>

      <!-- 试卷预览 -->
      <view class="create__preview">
        <view class="create__preview-item">
          <text class="create__preview-value">{{ questionCount }}</text>
          <text class="create__preview-label">题目数</text>
        </view>
        <view class="create__preview-item">
          <text class="create__preview-value">{{ durationMinutes }}</text>
          <text class="create__preview-label">时长(分)</text>
        </view>
        <view class="create__preview-item">
          <text class="create__preview-value">100</text>
          <text class="create__preview-label">总分</text>
        </view>
        <view class="create__preview-item">
          <text class="create__preview-value">60</text>
          <text class="create__preview-label">及格线</text>
        </view>
      </view>

      <view class="create__tips">
        <text class="create__tip">· 从题库随机抽题组卷，题型混合、等分计分</text>
        <text class="create__tip">· 考试中倒计时结束将自动交卷，请合理安排作答时间</text>
      </view>

      <view class="create__submit" :class="{ 'is-disabled': submitting || !currentBankId }" @tap="handleStart">
        {{ submitting ? '正在组卷…' : '开始考试' }}
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 发起考试 / 组卷（P-11）
 * 接口：API-EXM-001 发起试卷（POST /exam-papers）
 * 说明：题库列表复用 bankStore（未加载时拉取首页），支持 ?bank_id= 带入默认题库；
 *       组卷成功后跳转考试答题页 exam/paper?id=试卷id。
 */
import { computed, onMounted, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { createExamPaper } from '@/api/exam'
import { useBankStore } from '@/stores/bank'
import type { QuestionBank } from '@/types'

/** 可选时长（分钟） */
const DURATIONS = [15, 30, 45, 60, 90, 120]

const bankStore = useBankStore()
const banks = ref<QuestionBank[]>([])
const currentBankId = ref(0)
const questionCount = ref(30)
const durationMinutes = ref(60)
const submitting = ref(false)

/** 滑块上限：不超过题库题量（限制在 10~100） */
const maxCount = computed(() => {
  const bank = banks.value.find((item) => item.id === currentBankId.value)
  if (!bank || bank.question_count < 10) return 100
  return Math.min(100, bank.question_count)
})

const bankOptions = computed(() => banks.value)
const currentBankTitle = computed(
  () => banks.value.find((item) => item.id === currentBankId.value)?.title ?? '请选择题库'
)

onLoad((options) => {
  const fromQuery = options?.bank_id ? Number(options.bank_id) : 0
  if (fromQuery) currentBankId.value = fromQuery
})

onMounted(async () => {
  // bankStore 已有数据直接复用，否则拉取首页
  if (!bankStore.banks.length) {
    await bankStore.loadMyBanks(true)
  }
  banks.value = bankStore.banks
  // 未通过路由带入时，默认取上次学习的题库或第一个题库
  if (!currentBankId.value) {
    const last = bankStore.getLastBankId()
    currentBankId.value = last ?? banks.value[0]?.id ?? 0
  }
  // 题库题量少于当前题数时自动下调
  if (questionCount.value > maxCount.value) {
    questionCount.value = Math.max(10, maxCount.value)
  }
})

function onBankChange(e: { detail: { value: number } }) {
  currentBankId.value = banks.value[e.detail.value]?.id ?? 0
  if (questionCount.value > maxCount.value) {
    questionCount.value = Math.max(10, maxCount.value)
  }
}

function onCountChanging(e: { detail: { value: number } }) {
  questionCount.value = e.detail.value
}

function onCountChange(e: { detail: { value: number } }) {
  questionCount.value = e.detail.value
}

async function handleStart() {
  if (submitting.value) return
  if (!currentBankId.value) {
    uni.showToast({ title: '请先选择题库', icon: 'none' })
    return
  }
  // 防重复点击（loading 锁）
  submitting.value = true
  try {
    const paper = await createExamPaper({
      bank_id: currentBankId.value,
      question_count: questionCount.value,
      duration_minutes: durationMinutes.value
    })
    // 组卷成功进入答题页，替换当前页避免返回时重复组卷
    uni.redirectTo({ url: `/pages-sub/exam/paper?id=${paper.id}` })
  } finally {
    submitting.value = false
  }
}
</script>

<style lang="scss" scoped>
.create {
  min-height: 100vh;
  padding: $spacing-lg;
  background-color: $color-bg-page;

  &__card {
    padding: $spacing-lg;
    background-color: $color-bg-card;
    border-radius: $radius-xl;
    box-shadow: $shadow-sm;
  }

  &__field {
    padding: $spacing-md 0;
    border-bottom: 1rpx solid $color-divider;
  }

  &__field-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  &__label {
    font-size: $font-size-base;
    font-weight: 600;
    color: $color-text-primary;
  }

  &__field-value {
    font-size: $font-size-base;
    color: $color-primary;
    font-weight: 600;
  }

  &__picker {
    margin-top: $spacing-md;
  }

  &__picker-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 80rpx;
    padding: 0 $spacing-md;
    background-color: $color-bg-page;
    border-radius: $radius-md;
  }

  &__picker-text {
    font-size: $font-size-sm;
    color: $color-text-primary;
  }

  &__picker-arrow {
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__slider {
    margin: $spacing-md $spacing-xs 0;
  }

  &__slider-ticks {
    display: flex;
    justify-content: space-between;
    padding: 0 $spacing-xs;
    font-size: $font-size-xs;
    color: $color-text-placeholder;
  }

  &__durations {
    display: flex;
    flex-wrap: wrap;
    margin-top: $spacing-md;
  }

  &__duration {
    padding: 12rpx 28rpx;
    margin: 0 $spacing-sm $spacing-sm 0;
    border: 2rpx solid $color-border;
    border-radius: $radius-circle;
    font-size: $font-size-sm;
    color: $color-text-regular;

    &.is-active {
      border-color: $color-primary;
      background-color: $color-primary-bg;
      color: $color-primary;
      font-weight: 600;
    }
  }

  &__preview {
    display: flex;
    padding: $spacing-md 0;
    margin-top: $spacing-md;
  }

  &__preview-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  &__preview-value {
    font-size: $font-size-xl;
    font-weight: 700;
    color: $color-primary;
  }

  &__preview-label {
    margin-top: 4rpx;
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__tips {
    padding: $spacing-sm 0 $spacing-md;
  }

  &__tip {
    display: block;
    font-size: $font-size-xs;
    line-height: 1.9;
    color: $color-text-secondary;
  }

  &__submit {
    height: 88rpx;
    border-radius: 44rpx;
    background: $color-primary-gradient;
    color: $color-text-inverse;
    font-size: $font-size-lg;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;

    &.is-disabled {
      opacity: 0.5;
    }
  }
}
</style>
