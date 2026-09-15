<template>
  <view class="note">
    <!-- 筛选栏 -->
    <view class="note__filter">
      <picker class="note__picker" :range="bankOptions" range-key="title" @change="onBankChange">
        <view class="note__picker-inner">
          <text class="note__picker-text">{{ currentBankName }}</text>
          <text class="note__picker-arrow">▾</text>
        </view>
      </picker>
      <view class="note__search">
        <text class="note__search-icon">搜</text>
        <input
          v-model="keyword"
          class="note__search-input"
          type="text"
          placeholder="搜索笔记 / 题干"
          placeholder-class="note__search-placeholder"
          confirm-type="search"
          @confirm="handleSearch"
        />
        <text v-if="keyword" class="note__search-clear" @tap="handleClear">×</text>
      </view>
    </view>

    <view class="note__list">
      <template v-if="list.length">
        <view
          v-for="item in list"
          :key="item.id"
          class="note__item"
          @tap="goPractice(item)"
        >
          <text class="note__title text-ellipsis-2">{{ item.question_title }}</text>
          <view class="note__content">
            <text class="note__content-label">笔记</text>
            <text class="note__content-text">{{ item.content }}</text>
          </view>
          <view class="note__item-footer">
            <text class="note__bank">{{ item.bank_name }}</text>
            <view class="note__meta">
              <text class="note__like">赞 {{ item.like_count }}</text>
              <text class="note__time">{{ item.updated_at }}</text>
            </view>
          </view>
        </view>
      </template>

      <base-empty
        v-else-if="!loading"
        title="还没有笔记"
        desc="在答题页点「笔记」即可记录你的理解与记忆技巧"
        icon-text="记"
      />

      <list-load-more :loading="loading" :has-more="hasMore" />
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 我的笔记（P-21）
 * 接口：API-NOTE-001 我的笔记列表
 * 说明：点击卡片跳练习答题页，定位到所属题库继续学习。
 */
import { computed, onMounted, ref } from 'vue'
import { onReachBottom } from '@dcloudio/uni-app'
import { fetchNotes } from '@/api/note'
import { fetchMyBanks } from '@/api/bank'
import type { NoteItem, QuestionBank } from '@/types'

const list = ref<NoteItem[]>([])
const loading = ref(false)
const hasMore = ref(true)
const page = ref(1)
const pageSize = 20
const bankId = ref<number>()
const keyword = ref('')

const bankOptions = ref<QuestionBank[]>([])

const currentBankName = computed(() => {
  if (!bankId.value) return '全部题库'
  return bankOptions.value.find((b) => b.id === bankId.value)?.title ?? '全部题库'
})

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
    const res = await fetchNotes({
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

function goPractice(item: NoteItem) {
  uni.navigateTo({
    url: `/pages-sub/practice/answer?bank_id=${item.bank_id}&mode=sequence`
  })
}
</script>

<style lang="scss" scoped>
.note {
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
    padding: $spacing-md;
    margin-bottom: $spacing-sm;
    background-color: $color-bg-card;
    border-radius: $radius-lg;
  }

  &__title {
    display: block;
    font-size: $font-size-base;
    line-height: 1.6;
    color: $color-text-primary;
    margin-bottom: $spacing-sm;
  }

  &__content {
    padding: $spacing-sm;
    background-color: $color-warning-bg;
    border-radius: $radius-md;
  }

  &__content-label {
    display: inline-block;
    font-size: $font-size-xs;
    color: $color-warning;
    margin-right: 8rpx;
    font-weight: 600;
  }

  &__content-text {
    font-size: $font-size-sm;
    line-height: 1.7;
    color: $color-text-regular;
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

  &__meta {
    display: flex;
    align-items: center;
  }

  &__like {
    font-size: $font-size-xs;
    color: $color-danger;
    margin-right: $spacing-sm;
  }

  &__time {
    font-size: $font-size-xs;
    color: $color-text-placeholder;
  }
}
</style>
