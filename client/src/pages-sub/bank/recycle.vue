<template>
  <view class="recycle">
    <!-- 搜索 -->
    <view class="recycle__search-row">
      <view class="recycle__search">
        <text class="recycle__search-icon">搜</text>
        <input
          v-model="keyword"
          class="recycle__search-input"
          type="text"
          placeholder="搜索题库名称"
          placeholder-class="recycle__search-placeholder"
          confirm-type="search"
          @confirm="handleSearch"
        />
        <text v-if="keyword" class="recycle__search-clear" @tap="handleClear">×</text>
      </view>
      <text class="recycle__search-btn" @tap="handleSearch">搜索</text>
    </view>

    <view class="recycle__list">
      <template v-if="list.length">
        <view v-for="item in list" :key="item.id" class="recycle__item">
          <view class="recycle__item-header">
            <text class="recycle__title text-ellipsis">{{ item.title }}</text>
            <text class="recycle__count">{{ item.question_count }} 题</text>
          </view>
          <text class="recycle__deleted">删除于 {{ item.deleted_at }}</text>
          <view class="recycle__restore" @tap="handleRestore(item)">恢复</view>
        </view>
      </template>

      <base-empty
        v-else-if="!loading"
        title="回收站是空的"
        desc="误删的题库会在这里保留，可随时恢复"
        icon-text="收"
      />

      <list-load-more :loading="loading" :has-more="hasMore" />
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 回收站（P-26）
 * 接口：API-BANK-008 回收站列表、API-BANK-009 恢复题库
 * 说明：搜索筛选 + 「恢复」二次确认，成功后从列表移除并提示。
 */
import { ref } from 'vue'
import { onReachBottom } from '@dcloudio/uni-app'
import { fetchRecycleBanks, restoreBank } from '@/api/bank'
import type { RecycleBankItem } from '@/types'

const list = ref<RecycleBankItem[]>([])
const loading = ref(false)
const hasMore = ref(true)
const page = ref(1)
const pageSize = 20
const keyword = ref('')
const restoring = ref(false)

async function loadData(reset = false) {
  if (reset) {
    page.value = 1
    hasMore.value = true
    list.value = []
  }
  if (loading.value || (!reset && !hasMore.value)) return
  loading.value = true
  try {
    const res = await fetchRecycleBanks({
      page: page.value,
      page_size: pageSize,
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

function handleSearch() {
  loadData(true)
}

function handleClear() {
  keyword.value = ''
  loadData(true)
}

async function handleRestore(item: RecycleBankItem) {
  if (restoring.value) return
  uni.showModal({
    title: '恢复题库',
    content: `确定将「${item.title}」恢复到我的题库吗？`,
    success: async (modal) => {
      if (!modal.confirm) return
      restoring.value = true
      try {
        await restoreBank(item.id)
        list.value = list.value.filter((b) => b.id !== item.id)
        uni.showToast({ title: '已恢复', icon: 'none' })
      } finally {
        restoring.value = false
      }
    }
  })
}

loadData(true)
</script>

<style lang="scss" scoped>
.recycle {
  min-height: 100vh;
  background-color: $color-bg-page;

  &__search-row {
    display: flex;
    align-items: center;
    padding: $spacing-sm $spacing-lg;
    background-color: $color-bg-card;
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

  &__search-btn {
    margin-left: $spacing-md;
    font-size: $font-size-base;
    color: $color-primary;
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
    justify-content: space-between;
    margin-bottom: $spacing-xs;
  }

  &__title {
    flex: 1;
    font-size: $font-size-base;
    font-weight: 600;
    color: $color-text-primary;
    min-width: 0;
  }

  &__count {
    flex-shrink: 0;
    margin-left: $spacing-sm;
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__deleted {
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
