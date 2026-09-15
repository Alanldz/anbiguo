<template>
  <view class="favorite">
    <!-- 筛选栏 -->
    <view class="favorite__filter">
      <picker class="favorite__picker" :range="bankOptions" range-key="title" @change="onBankChange">
        <view class="favorite__picker-inner">
          <text class="favorite__picker-text">{{ currentBankName }}</text>
          <text class="favorite__picker-arrow">▾</text>
        </view>
      </picker>
      <view class="favorite__search">
        <text class="favorite__search-icon">搜</text>
        <input
          v-model="keyword"
          class="favorite__search-input"
          type="text"
          placeholder="搜索题干 / 题库"
          placeholder-class="favorite__search-placeholder"
          confirm-type="search"
          @confirm="handleSearch"
        />
        <text v-if="keyword" class="favorite__search-clear" @tap="handleClear">×</text>
      </view>
    </view>

    <view class="favorite__list">
      <template v-if="list.length">
        <view
          v-for="item in list"
          :key="item.id"
          class="favorite__item"
          @tap="goPractice(item)"
        >
          <view class="favorite__item-header">
            <text class="favorite__type" :class="typeClass(item.question_type)">{{ typeLabel(item.question_type) }}</text>
            <text v-if="item.question_difficulty" class="favorite__diff">{{ difficultyLabel(item.question_difficulty) }}</text>
            <text class="favorite__folder">{{ item.folder_name }}</text>
          </view>
          <text class="favorite__title text-ellipsis-2">{{ item.question_title }}</text>
          <view class="favorite__item-footer">
            <text class="favorite__bank">{{ item.bank_name }}</text>
            <text class="favorite__time">{{ item.created_at }}</text>
          </view>
          <view class="favorite__cancel" @tap.stop="handleCancel(item)">取消收藏</view>
        </view>
      </template>

      <base-empty
        v-else-if="!loading"
        title="还没有收藏"
        desc="练习时遇到好题，点底部「收藏」即可收录到这里"
        icon-text="藏"
      />

      <list-load-more :loading="loading" :has-more="hasMore" />
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 我的收藏（P-20）
 * 接口：API-FAV-001 我的收藏列表、API-QUE-003 取消收藏（toggleFavorite）
 * 说明：取消收藏二次确认后调用 toggleFavorite(question_id, false)；
 *       点击卡片跳练习答题页（参考 wrong/list 的跳转方式）。
 */
import { computed, onMounted, ref } from 'vue'
import { onReachBottom } from '@dcloudio/uni-app'
import { fetchFavorites } from '@/api/favorite'
import { toggleFavorite } from '@/api/question'
import { fetchMyBanks } from '@/api/bank'
import { QuestionType, type FavoriteItem, type QuestionBank } from '@/types'

const list = ref<FavoriteItem[]>([])
const loading = ref(false)
const hasMore = ref(true)
const page = ref(1)
const pageSize = 20
const bankId = ref<number>()
const keyword = ref('')

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
    const res = await fetchFavorites({
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

function goPractice(item: FavoriteItem) {
  uni.navigateTo({
    url: `/pages-sub/practice/answer?bank_id=${item.bank_id}&mode=sequence`
  })
}

async function handleCancel(item: FavoriteItem) {
  uni.showModal({
    title: '取消收藏',
    content: '确定不再收藏本题吗？',
    success: async (modal) => {
      if (!modal.confirm) return
      await toggleFavorite(item.question_id, false)
      list.value = list.value.filter((f) => f.id !== item.id)
      uni.showToast({ title: '已取消收藏', icon: 'none' })
    }
  })
}
</script>

<style lang="scss" scoped>
.favorite {
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

  &__folder {
    margin-left: auto;
    font-size: $font-size-xs;
    color: $color-text-secondary;
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

  &__cancel {
    position: absolute;
    right: $spacing-md;
    bottom: $spacing-md;
    font-size: $font-size-xs;
    color: $color-danger;
  }
}
</style>
