<template>
  <view class="master">
    <!-- 筛选栏 -->
    <view class="master__filter">
      <picker class="master__picker" :range="bankOptions" range-key="title" @change="onBankChange">
        <view class="master__picker-inner">
          <text class="master__picker-text">{{ currentBankName }}</text>
          <text class="master__picker-arrow">▾</text>
        </view>
      </picker>
      <view class="master__search">
        <text class="master__search-icon">搜</text>
        <input
          v-model="keyword"
          class="master__search-input"
          type="text"
          placeholder="搜索题干 / 题库"
          placeholder-class="master__search-placeholder"
          confirm-type="search"
          @confirm="handleSearch"
        />
        <text v-if="keyword" class="master__search-clear" @tap="handleClear">×</text>
      </view>
    </view>

    <view class="master__list">
      <template v-if="list.length">
        <view
          v-for="item in list"
          :key="item.id"
          class="master__item"
          @tap="goPractice(item)"
        >
          <view class="master__item-header">
            <text class="master__type" :class="typeClass(item.question_type)">{{ typeLabel(item.question_type) }}</text>
            <text v-if="item.question_difficulty" class="master__diff">{{ difficultyLabel(item.question_difficulty) }}</text>
            <text class="master__wrong">累计答错 {{ item.wrong_count }} 次</text>
          </view>
          <text class="master__title text-ellipsis-2">{{ item.question_title }}</text>
          <view class="master__item-footer">
            <text class="master__bank">{{ item.bank_name }}</text>
            <text class="master__time">斩于 {{ item.mastered_at }}</text>
          </view>
          <view class="master__restore" @tap.stop="handleRestore(item)">找回</view>
        </view>
      </template>

      <base-empty
        v-else-if="!loading"
        title="还没有斩掉的题"
        desc="连续答对即可斩题，斩掉的题会收录到这里"
        icon-text="斩"
      />

      <list-load-more :loading="loading" :has-more="hasMore" />
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 我的斩题（P-29）
 * 接口：API-MST-001 斩题列表、API-MST-002 找回
 * 说明：
 *  - 筛选：题库下拉 + 关键词搜索；
 *  - 点击卡片跳练习答题页（顺序练习，同 favorite/list 的跳转方式）；
 *  - 「找回」二次确认 → restore → 列表移除并提示。
 */
import { computed, onMounted, ref } from 'vue'
import { onReachBottom } from '@dcloudio/uni-app'
import { fetchMastered, restoreMastered } from '@/api/master'
import { fetchMyBanks } from '@/api/bank'
import { QuestionType, type MasteredItem, type QuestionBank } from '@/types'

const list = ref<MasteredItem[]>([])
const loading = ref(false)
const hasMore = ref(true)
const page = ref(1)
const pageSize = 20
const bankId = ref<number>()
const keyword = ref('')
const restoring = ref(false)

const bankOptions = ref<QuestionBank[]>([])

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

const currentBankName = computed(() => {
  if (!bankId.value) return '全部题库'
  return bankOptions.value.find((b) => b.id === bankId.value)?.title ?? '全部题库'
})

function typeLabel(type: QuestionType): string {
  return TYPE_LABEL[type] ?? '题目'
}

function typeClass(type: QuestionType): string {
  return type === QuestionType.Multiple ? 'is-multi' : ''
}

function difficultyLabel(level: number): string {
  return DIFFICULTY_LABEL[level] ?? ''
}

onMounted(() => {
  fetchMyBanks({ page: 1, page_size: 50 }).then((res) => {
    bankOptions.value = res.list
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
    const res = await fetchMastered({
      page: page.value,
      page_size: pageSize,
      bank_id: bankId.value,
      keyword: keyword.value || undefined
    })
    list.value = reset ? res.list : [...list.value, ...res.list]
    page.value += 1
    hasMore.value = res.pagination.page < res.pagination.total_pages
  } finally {
    loading.value = false
  }
}

onReachBottom(() => loadData(false))

function onBankChange(e: { detail: { value: number } }) {
  const index = e.detail.value
  bankId.value = bankOptions.value[index]?.id
  loadData(true)
}

function handleSearch() {
  loadData(true)
}

function handleClear() {
  keyword.value = ''
  loadData(true)
}

function goPractice(item: MasteredItem) {
  uni.navigateTo({
    url: `/pages-sub/practice/answer?bank_id=${item.bank_id}&mode=sequence`
  })
}

function handleRestore(item: MasteredItem) {
  if (restoring.value) return
  uni.showModal({
    title: '找回题目',
    content: '找回后将重新进入日常练习与错题统计，确定找回吗？',
    success: async (modal) => {
      if (!modal.confirm) return
      restoring.value = true
      try {
        await restoreMastered(item.id)
        list.value = list.value.filter((m) => m.id !== item.id)
        uni.showToast({ title: '已找回', icon: 'none' })
      } finally {
        restoring.value = false
      }
    }
  })
}
</script>

<style lang="scss" scoped>
.master {
  min-height: 100vh;
  background-color: $color-bg-page;

  &__filter {
    display: flex;
    align-items: center;
    padding: $spacing-sm $spacing-lg;
    background-color: $color-bg-card;
    position: sticky;
    top: 0;
    z-index: 10;
  }

  &__picker {
    flex-shrink: 0;
    margin-right: $spacing-sm;
  }

  &__picker-inner {
    display: flex;
    align-items: center;
    height: 68rpx;
    max-width: 240rpx;
    padding: 0 $spacing-md;
    background-color: $color-bg-page;
    border-radius: 34rpx;
  }

  &__picker-text {
    font-size: $font-size-sm;
    color: $color-text-primary;
    max-width: 160rpx;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  &__picker-arrow {
    margin-left: 6rpx;
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__search {
    flex: 1;
    height: 68rpx;
    display: flex;
    align-items: center;
    padding: 0 $spacing-md;
    background-color: $color-bg-page;
    border-radius: 34rpx;
  }

  &__search-icon {
    font-size: $font-size-sm;
    color: $color-text-secondary;
    margin-right: $spacing-xs;
  }

  &__search-input {
    flex: 1;
    font-size: $font-size-sm;
    color: $color-text-primary;
  }

  &__search-placeholder {
    color: $color-text-placeholder;
  }

  &__search-clear {
    font-size: $font-size-lg;
    color: $color-text-placeholder;
    padding: 0 $spacing-xs;
  }

  &__list {
    padding: $spacing-lg;
  }

  &__item {
    position: relative;
    padding: $spacing-md;
    padding-bottom: 72rpx;
    margin-bottom: $spacing-sm;
    background-color: $color-bg-card;
    border-radius: $radius-lg;
  }

  &__item-header {
    display: flex;
    align-items: center;
    margin-bottom: $spacing-sm;
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

  &__wrong {
    margin-left: auto;
    font-size: $font-size-xs;
    color: $color-danger;
  }

  &__title {
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

  &__bank {
    font-size: $font-size-xs;
    color: $color-text-regular;
  }

  &__time {
    font-size: $font-size-xs;
    color: $color-text-placeholder;
  }

  &__restore {
    position: absolute;
    right: $spacing-md;
    bottom: $spacing-md;
    padding: 8rpx 28rpx;
    font-size: $font-size-sm;
    color: $color-primary;
    background-color: $color-primary-bg;
    border-radius: 28rpx;
  }
}
</style>
