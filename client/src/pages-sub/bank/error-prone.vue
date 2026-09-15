<template>
  <view class="epq">
    <!-- 题库名 -->
    <view class="epq__header">
      <text class="epq__header-label">当前题库</text>
      <text class="epq__header-name">{{ bankTitle || '加载中…' }}</text>
    </view>

    <view class="epq__list">
      <template v-if="list.length">
        <view
          v-for="item in list"
          :key="item.id"
          class="epq__item"
          @tap="goPractice(item)"
        >
          <view class="epq__rate" :class="rateClass(item.correct_rate)">
            <text class="epq__rate-num">{{ item.correct_rate }}%</text>
            <text class="epq__rate-label">正确率</text>
          </view>
          <view class="epq__main">
            <text class="epq__title text-ellipsis-2">{{ item.question_title }}</text>
            <view class="epq__meta">
              <text class="epq__type" :class="typeClass(item.question_type)">{{ typeLabel(item.question_type) }}</text>
              <text v-if="item.question_difficulty" class="epq__diff">{{ difficultyLabel(item.question_difficulty) }}</text>
              <text class="epq__count">{{ formatCount(item.answer_count) }}人答过</text>
              <text v-if="item.is_wrong" class="epq__in-wrong">已在错题本</text>
            </view>
          </view>
        </view>
      </template>

      <base-empty
        v-else-if="!loading"
        title="暂无易错题"
        desc="全网用户错误率较高的题目会出现在这里"
        icon-text="易"
      />

      <list-load-more :loading="loading" :has-more="hasMore" />
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 易错题集（P-30）
 * 接口：API-ERR-001 易错题集列表（bank_id 必填）、API-BANK-003 题库详情（顶部题库名）
 * 说明：
 *  - onLoad 接收 bank_id 参数；
 *  - 正确率颜色：<30% 红 / <50% 橙 / 其余灰；
 *  - is_wrong 为 true 时显示「已在错题本」标记；
 *  - 点击卡片跳练习答题页。
 */
import { ref } from 'vue'
import { onLoad, onReachBottom } from '@dcloudio/uni-app'
import { fetchErrorProne } from '@/api/question'
import { fetchBankDetail } from '@/api/bank'
import { QuestionType, type ErrorProneItem } from '@/types'

const list = ref<ErrorProneItem[]>([])
const loading = ref(false)
const hasMore = ref(true)
const page = ref(1)
const pageSize = 20
const bankId = ref(0)
const bankTitle = ref('')

const TYPE_LABEL: Record<number, string> = {
  [QuestionType.Single]: '单选',
  [QuestionType.Multiple]: '多选',
  [QuestionType.Judge]: '判断',
  [QuestionType.Blank]: '填空',
  [QuestionType.Essay]: '简答'
}

const DIFFICULTY_LABEL: Record<number, string> = {
  1: '入门',
  2: '简单',
  3: '中等',
  4: '较难',
  5: '困难'
}

function typeLabel(type: QuestionType): string {
  return TYPE_LABEL[type] ?? '题目'
}

function typeClass(type: QuestionType): string {
  return type === QuestionType.Multiple ? 'is-multi' : ''
}

function difficultyLabel(level: number): string {
  return DIFFICULTY_LABEL[level] ?? ''
}

/** 正确率颜色分级：<30% 红 / <50% 橙 / 其余灰 */
function rateClass(rate: number): string {
  if (rate < 30) return 'is-danger'
  if (rate < 50) return 'is-warning'
  return ''
}

/** 答题人数简写：数百~数千 */
function formatCount(count: number): string {
  if (count >= 10000) return `${(count / 10000).toFixed(1)}w`
  if (count >= 1000) return `${(count / 1000).toFixed(1)}k`
  return String(count)
}

onLoad((options) => {
  bankId.value = Number(options?.bank_id ?? 0)
  if (!bankId.value) return
  // 顶部展示题库名
  fetchBankDetail(bankId.value).then((bank) => {
    bankTitle.value = bank.title
  })
  loadData(true)
})

async function loadData(reset = false) {
  if (reset) {
    page.value = 1
    hasMore.value = true
    list.value = []
  }
  if (loading.value || (!reset && !hasMore.value)) return
  loading.value = true
  try {
    const res = await fetchErrorProne(bankId.value, {
      page: page.value,
      page_size: pageSize
    })
    list.value = reset ? res.list : [...list.value, ...res.list]
    page.value += 1
    hasMore.value = res.pagination.page < res.pagination.total_pages
  } finally {
    loading.value = false
  }
}

onReachBottom(() => loadData(false))

function goPractice(item: ErrorProneItem) {
  uni.navigateTo({
    url: `/pages-sub/practice/answer?bank_id=${item.bank_id}&mode=sequence`
  })
}
</script>

<style lang="scss" scoped>
.epq {
  min-height: 100vh;
  background-color: $color-bg-page;

  &__header {
    display: flex;
    align-items: center;
    padding: $spacing-sm $spacing-lg;
    background-color: $color-bg-card;
  }

  &__header-label {
    flex-shrink: 0;
    margin-right: $spacing-sm;
    font-size: $font-size-xs;
    color: $color-text-placeholder;
  }

  &__header-name {
    font-size: $font-size-sm;
    font-weight: 600;
    color: $color-text-primary;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  &__list {
    padding: $spacing-lg;
  }

  &__item {
    display: flex;
    align-items: flex-start;
    padding: $spacing-md;
    margin-bottom: $spacing-sm;
    background-color: $color-bg-card;
    border-radius: $radius-lg;
  }

  &__rate {
    flex-shrink: 0;
    width: 120rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: $spacing-sm 0;
    margin-right: $spacing-md;
    border-radius: $radius-md;
    background-color: $color-bg-page;

    &.is-danger .epq__rate-num {
      color: $color-danger;
    }

    &.is-warning .epq__rate-num {
      color: $color-warning;
    }

    &.is-danger {
      background-color: $color-danger-bg;
    }

    &.is-warning {
      background-color: $color-warning-bg;
    }
  }

  &__rate-num {
    font-size: $font-size-lg;
    font-weight: 700;
    color: $color-text-secondary;
  }

  &__rate-label {
    margin-top: 2rpx;
    font-size: $font-size-xs;
    color: $color-text-placeholder;
  }

  &__main {
    flex: 1;
    min-width: 0;
  }

  &__title {
    display: block;
    font-size: $font-size-base;
    line-height: 1.6;
    color: $color-text-primary;
  }

  &__meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    margin-top: $spacing-sm;
  }

  &__type {
    font-size: $font-size-xs;
    color: $color-primary;
    background-color: $color-primary-bg;
    padding: 2rpx 10rpx;
    border-radius: $radius-sm;

    &.is-multi {
      color: $color-warning;
      background-color: $color-warning-bg;
    }
  }

  &__diff {
    margin-left: $spacing-xs;
    font-size: $font-size-xs;
    color: $color-text-secondary;
    background-color: $color-bg-page;
    padding: 2rpx 10rpx;
    border-radius: $radius-sm;
  }

  &__count {
    margin-left: auto;
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__in-wrong {
    margin-left: $spacing-sm;
    font-size: $font-size-xs;
    color: $color-danger;
    background-color: $color-danger-bg;
    padding: 2rpx 10rpx;
    border-radius: $radius-sm;
  }
}
</style>
