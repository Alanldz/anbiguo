<template>
  <view class="search-page">
    <!-- 搜索栏 -->
    <view class="search-page__bar">
      <view class="search-page__input-wrap">
        <text class="search-page__input-icon">搜</text>
        <input
          v-model="keyword"
          class="search-page__input"
          type="text"
          :placeholder="searchPlaceholder"
          placeholder-class="search-page__input-placeholder"
          confirm-type="search"
          @confirm="handleSearch"
        />
        <text v-if="keyword" class="search-page__input-clear" @tap="handleClear">×</text>
      </view>
      <text class="search-page__btn" @tap="handleSearch">搜索</text>
    </view>

    <!-- 范围提示 / 题型筛选 -->
    <view class="search-page__filter">
      <text class="search-page__scope">{{ scopeText }}</text>
      <view class="search-page__types">
        <view
          v-for="item in typeOptions"
          :key="item.value"
          class="search-page__type"
          :class="{ 'is-active': activeType === item.value }"
          @tap="handleType(item.value)"
        >
          {{ item.label }}
        </view>
      </view>
    </view>

    <!-- AI 拍照搜题入口（AI 能力待接入，保留入口） -->
    <view class="search-page__ai" @tap="handleAiSearch">
      <text class="search-page__ai-icon">AI</text>
      <view class="search-page__ai-info">
        <text class="search-page__ai-title">AI 拍照搜题</text>
        <text class="search-page__ai-desc">拍一拍，题目解析马上出来</text>
      </view>
      <text class="search-page__ai-arrow">›</text>
    </view>

    <!-- 搜索结果列表 -->
    <view class="search-page__list">
      <template v-if="list.length">
        <view v-for="item in list" :key="item.id" class="search-page__item" @tap="goPractice(item)">
          <view class="search-page__item-header">
            <text class="search-page__item-type">{{ typeLabel(item.type) }}</text>
            <text v-if="item.bank_name" class="search-page__item-bank text-ellipsis">{{ item.bank_name }}</text>
          </view>
          <text class="search-page__item-title text-ellipsis-2">{{ item.title }}</text>
          <view class="search-page__item-footer">
            <text class="search-page__item-answer">答案：{{ item.answer }}</text>
            <text class="search-page__item-link">去练习 ›</text>
          </view>
        </view>
      </template>

      <base-empty
        v-else-if="!loading && searched"
        title="没有找到相关题目"
        desc="换个关键词试试，或到题库市场找找合适的题库"
        icon-text="搜"
      />

      <list-load-more :loading="loading" :has-more="hasMore" />
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 搜索 / 搜题（P-22）
 * 接口：API-SRC-001 题库内关键词搜索（GET /search/questions）
 * 入口参数：mode=bank（题库内搜索）、bank_id（限定题库 id，可选）
 * 说明：AI 拍照搜题依赖 AI 能力（SRC-002 不在本期契约），点击提示「AI 搜题待接入」。
 *       练习答题页暂不支持按 question_id 定位，故点击结果跳题库练习页。
 */
import { computed, ref } from 'vue'
import { onLoad, onReachBottom } from '@dcloudio/uni-app'
import { searchQuestions } from '@/api/question'
import { QuestionType } from '@/types'
import type { SearchQuestionItem } from '@/types'

const TYPE_LABEL: Record<number, string> = {
  [QuestionType.Single]: '单选',
  [QuestionType.Multiple]: '多选',
  [QuestionType.Judge]: '判断',
  [QuestionType.Blank]: '填空',
  [QuestionType.Essay]: '简答'
}

const typeOptions = [
  { label: '全部', value: 0 },
  { label: '单选', value: QuestionType.Single },
  { label: '多选', value: QuestionType.Multiple },
  { label: '判断', value: QuestionType.Judge },
  { label: '填空', value: QuestionType.Blank },
  { label: '简答', value: QuestionType.Essay }
]

const keyword = ref('')
const activeType = ref(0)
const bankId = ref(0)
const list = ref<SearchQuestionItem[]>([])
const loading = ref(false)
const hasMore = ref(true)
const page = ref(1)
const pageSize = 20
/** 是否已执行过搜索（控制空状态展示时机） */
const searched = ref(false)

/** 是否限定题库内搜索（带 bank_id 入参时） */
const isBankScope = computed(() => bankId.value > 0)

const searchPlaceholder = computed(() =>
  isBankScope.value ? '搜索当前题库中的题目' : '输入题干关键词搜索题目'
)

const scopeText = computed(() =>
  isBankScope.value ? `范围：题库 #${bankId.value}` : '范围：我的题库（可从题库详情页进入限定搜索）'
)

onLoad((options) => {
  bankId.value = Number(options?.bank_id ?? 0)
})

function typeLabel(type: QuestionType): string {
  return TYPE_LABEL[type] ?? '题目'
}

async function loadData(reset = false) {
  const kw = keyword.value.trim()
  if (!kw) {
    uni.showToast({ title: '请输入搜索关键词', icon: 'none' })
    return
  }
  if (reset) {
    page.value = 1
    hasMore.value = true
    list.value = []
  }
  if (loading.value || (!reset && !hasMore.value)) return
  loading.value = true
  searched.value = true
  try {
    const res = await searchQuestions({
      page: page.value,
      page_size: pageSize,
      keyword: kw,
      bank_id: isBankScope.value ? bankId.value : undefined,
      type: activeType.value || undefined
    })
    list.value = reset ? res.list : [...list.value, ...res.list]
    page.value += 1
    hasMore.value = res.pagination.page < res.pagination.total_pages
  } finally {
    loading.value = false
  }
}

onReachBottom(() => loadData(false))

function handleSearch() {
  loadData(true)
}

function handleClear() {
  keyword.value = ''
  list.value = []
  searched.value = false
}

function handleType(value: number) {
  if (activeType.value === value) return
  activeType.value = value
  if (searched.value) loadData(true)
}

function goPractice(item: SearchQuestionItem) {
  // 练习答题页暂不支持按题目 id 定位，跳题库练习页（顺序模式）
  uni.navigateTo({ url: `/pages-sub/practice/answer?bank_id=${item.bank_id}&mode=sequence` })
}

function handleAiSearch() {
  uni.showToast({ title: 'AI 搜题待接入，敬请期待', icon: 'none' })
}
</script>

<style lang="scss" scoped>
.search-page {
  min-height: 100vh;
  background-color: $color-bg-page;

  &__bar {
    display: flex;
    align-items: center;
    padding: $spacing-sm $spacing-lg;
    background-color: $color-bg-card;
  }

  &__input-wrap {
    flex: 1;
    display: flex;
    align-items: center;
    height: 68rpx;
    padding: 0 $spacing-md;
    background-color: $color-bg-page;
    border-radius: 34rpx;
  }

  &__input-icon {
    font-size: $font-size-sm;
    color: $color-text-secondary;
    margin-right: $spacing-xs;
  }

  &__input {
    flex: 1;
    font-size: $font-size-sm;
    color: $color-text-primary;
  }

  &__input-placeholder {
    color: $color-text-placeholder;
  }

  &__input-clear {
    font-size: $font-size-lg;
    color: $color-text-placeholder;
    padding: 0 $spacing-xs;
  }

  &__btn {
    margin-left: $spacing-md;
    font-size: $font-size-base;
    color: $color-primary;
  }

  &__filter {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: $spacing-sm $spacing-lg;
    background-color: $color-bg-card;
  }

  &__scope {
    flex-shrink: 0;
    font-size: $font-size-xs;
    color: $color-text-secondary;
    max-width: 300rpx;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  &__types {
    display: flex;
    align-items: center;
  }

  &__type {
    margin-left: $spacing-sm;
    font-size: $font-size-xs;
    color: $color-text-regular;
    padding: 4rpx 16rpx;
    border-radius: $radius-circle;
    background-color: $color-bg-page;

    &.is-active {
      color: $color-text-inverse;
      background-color: $color-primary;
      font-weight: 600;
    }
  }

  &__ai {
    display: flex;
    align-items: center;
    margin: $spacing-md $spacing-lg 0;
    padding: $spacing-md;
    background: linear-gradient(90deg, #f3eeff 0%, #eaf2ff 100%);
    border-radius: $radius-lg;
  }

  &__ai-icon {
    width: 72rpx;
    height: 72rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: $radius-lg;
    background-color: #8b5cf6;
    color: $color-text-inverse;
    font-size: $font-size-sm;
    font-weight: 700;
  }

  &__ai-info {
    flex: 1;
    margin-left: $spacing-md;
  }

  &__ai-title {
    display: block;
    font-size: $font-size-base;
    font-weight: 600;
    color: $color-text-primary;
  }

  &__ai-desc {
    display: block;
    margin-top: 4rpx;
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__ai-arrow {
    font-size: $font-size-lg;
    color: $color-text-placeholder;
  }

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
    margin-bottom: $spacing-xs;
  }

  &__item-type {
    flex-shrink: 0;
    font-size: $font-size-xs;
    color: $color-primary;
    background-color: $color-primary-bg;
    padding: 2rpx 10rpx;
    border-radius: $radius-sm;
  }

  &__item-bank {
    margin-left: auto;
    max-width: 400rpx;
    font-size: $font-size-xs;
    color: $color-text-placeholder;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  &__item-title {
    display: block;
    font-size: $font-size-base;
    line-height: 1.6;
    color: $color-text-primary;
  }

  &__item-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: $spacing-sm;
  }

  &__item-answer {
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__item-link {
    font-size: $font-size-xs;
    color: $color-primary;
  }
}
</style>
