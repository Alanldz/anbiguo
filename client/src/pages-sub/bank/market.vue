<template>
  <view class="market">
    <!-- 搜索 + 分类筛选 -->
    <view class="market__filter">
      <view class="market__search">
        <text class="market__search-icon">搜</text>
        <input
          v-model="keyword"
          class="market__search-input"
          type="text"
          placeholder="搜索考试名称，如「考研」「一建」"
          placeholder-class="market__search-placeholder"
          confirm-type="search"
          @confirm="handleSearch"
        />
        <text v-if="keyword" class="market__search-clear" @tap="handleClear">×</text>
      </view>
      <scroll-view scroll-x class="market__categories">
        <view
          v-for="category in categories"
          :key="category.id"
          class="market__category"
          :class="{ 'is-active': activeCategoryId === category.id }"
          @tap="handleCategory(category.id)"
        >
          {{ category.name }}
        </view>
      </scroll-view>
    </view>

    <!-- 题库卡片列表 -->
    <view class="market__list">
      <template v-if="list.length">
        <view v-for="item in list" :key="item.id" class="market__item" @tap="goDetail(item.id)">
          <view class="market__item-header">
            <text class="market__item-title text-ellipsis">{{ item.title }}</text>
            <text v-if="item.is_recommend" class="market__item-recommend">荐</text>
          </view>
          <text class="market__item-desc text-ellipsis-2">{{ item.description || '暂无简介' }}</text>
          <view class="market__item-meta">
            <text class="market__item-meta-text">{{ item.question_count }} 题</text>
            <text class="market__item-meta-text">{{ formatUsage(item.usage_count) }} 人在用</text>
            <text
              class="market__item-add"
              @tap.stop="showDetail(item)"
            >添加到我的题库</text>
          </view>
        </view>
      </template>

      <base-empty
        v-else-if="!loading"
        title="没有找到相关题库"
        desc="换个关键词或分类试试"
        icon-text="市"
      />

      <list-load-more :loading="loading" :has-more="hasMore" />
    </view>

    <!-- 详情弹层（契约暂无「添加到我的题库」接口，按钮改为展示详情弹层） -->
    <view v-if="detailItem" class="market__popup-mask" @tap="detailItem = null">
      <view class="market__popup" @tap.stop>
        <text class="market__popup-title">{{ detailItem.title }}</text>
        <text class="market__popup-desc">{{ detailItem.description || '暂无简介' }}</text>
        <view class="market__popup-meta">
          <text class="market__popup-meta-item">题量：{{ detailItem.question_count }} 题</text>
          <text class="market__popup-meta-item">{{ formatUsage(detailItem.usage_count) }} 人在用</text>
          <text class="market__popup-meta-item">更新：{{ detailItem.created_at }}</text>
        </view>
        <view class="market__popup-actions">
          <view class="market__popup-btn market__popup-btn--ghost" @tap="detailItem = null">关闭</view>
          <view class="market__popup-btn" @tap="goDetail(detailItem.id)">进入题库</view>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 题库市场（P-08）
 * 接口：API-BANK-007 题库市场列表、API-BANK-001 分类列表
 * 说明：搜索 + 分类筛选 + 分页卡片（题库名/题数/使用人数/简介）；
 *       契约暂无「添加到我的题库」接口，按钮改为展示详情弹层；
 *       点击卡片跳题库详情 bank/detail?id=。
 */
import { onMounted, ref } from 'vue'
import { onReachBottom } from '@dcloudio/uni-app'
import { fetchCategories } from '@/api/bank'
import { fetchMarketBanks } from '@/api/market'
import type { BankCategory, MarketBankItem } from '@/types'

const list = ref<MarketBankItem[]>([])
const loading = ref(false)
const hasMore = ref(true)
const page = ref(1)
const pageSize = 20
const keyword = ref('')
const activeCategoryId = ref(0)
const categories = ref<BankCategory[]>([])
/** 详情弹层当前项 */
const detailItem = ref<MarketBankItem | null>(null)

onMounted(() => {
  fetchCategories().then((res) => {
    categories.value = res
  })
  loadData(true)
})

/** 使用人数格式化：万级缩写 */
function formatUsage(count: number): string {
  if (count >= 10000) return `${(count / 10000).toFixed(1)}万`
  return String(count)
}

async function loadData(reset = false) {
  if (reset) {
    page.value = 1
    hasMore.value = true
    list.value = []
  }
  if (loading.value || (!reset && !hasMore.value)) return
  loading.value = true
  try {
    const res = await fetchMarketBanks({
      page: page.value,
      page_size: pageSize,
      keyword: keyword.value.trim() || undefined,
      category_id: activeCategoryId.value || undefined
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

function handleCategory(id: number) {
  if (activeCategoryId.value === id) return
  activeCategoryId.value = id
  loadData(true)
}

/** 「添加到我的题库」：契约暂无对应接口，展示详情弹层 */
function showDetail(item: MarketBankItem) {
  detailItem.value = item
}

function goDetail(id: number) {
  uni.navigateTo({ url: `/pages-sub/bank/detail?id=${id}` })
}
</script>

<style lang="scss" scoped>
.market {
  min-height: 100vh;
  background-color: $color-bg-page;

  &__filter {
    position: sticky;
    top: 0;
    z-index: 10;
    padding: $spacing-sm $spacing-lg;
    background-color: $color-bg-card;
  }

  &__search {
    display: flex;
    align-items: center;
    height: 68rpx;
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

  &__categories {
    margin-top: $spacing-sm;
    white-space: nowrap;
  }

  &__category {
    display: inline-block;
    margin-right: $spacing-sm;
    padding: 8rpx 24rpx;
    font-size: $font-size-xs;
    color: $color-text-regular;
    background-color: $color-bg-page;
    border-radius: $radius-circle;

    &.is-active {
      color: $color-text-inverse;
      background-color: $color-primary;
      font-weight: 600;
    }
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

  &__item-title {
    flex: 1;
    margin-right: $spacing-sm;
    font-size: $font-size-base;
    font-weight: 600;
    color: $color-text-primary;
  }

  &__item-recommend {
    flex-shrink: 0;
    font-size: $font-size-xs;
    color: $color-text-inverse;
    background-color: $color-danger;
    padding: 2rpx 10rpx;
    border-radius: $radius-sm;
  }

  &__item-desc {
    display: block;
    font-size: $font-size-xs;
    line-height: 1.6;
    color: $color-text-secondary;
  }

  &__item-meta {
    display: flex;
    align-items: center;
    margin-top: $spacing-sm;
  }

  &__item-meta-text {
    margin-right: $spacing-md;
    font-size: $font-size-xs;
    color: $color-text-placeholder;
  }

  &__item-add {
    margin-left: auto;
    font-size: $font-size-xs;
    color: $color-primary;
    border: 2rpx solid $color-primary;
    padding: 8rpx 24rpx;
    border-radius: 28rpx;
  }

  &__popup-mask {
    position: fixed;
    inset: 0;
    z-index: 999;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(0, 0, 0, 0.5);
  }

  &__popup {
    width: 600rpx;
    padding: $spacing-lg;
    background-color: $color-bg-card;
    border-radius: $radius-xl;
  }

  &__popup-title {
    display: block;
    font-size: $font-size-lg;
    font-weight: 700;
    color: $color-text-primary;
  }

  &__popup-desc {
    display: block;
    margin-top: $spacing-sm;
    font-size: $font-size-sm;
    line-height: 1.7;
    color: $color-text-secondary;
  }

  &__popup-meta {
    display: flex;
    flex-wrap: wrap;
    margin-top: $spacing-md;
  }

  &__popup-meta-item {
    width: 50%;
    margin-bottom: $spacing-xs;
    font-size: $font-size-xs;
    color: $color-text-regular;
  }

  &__popup-actions {
    display: flex;
    margin-top: $spacing-lg;
  }

  &__popup-btn {
    flex: 1;
    height: 76rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: $font-size-base;
    color: $color-text-inverse;
    background-color: $color-primary;
    border-radius: 38rpx;

    &--ghost {
      margin-right: $spacing-sm;
      color: $color-text-regular;
      background-color: $color-bg-page;
    }
  }
}
</style>
