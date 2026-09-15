<template>
  <view class="bank-page">
    <!-- 搜索 -->
    <view class="bank-page__search-row">
      <view class="bank-page__search">
        <text class="bank-page__search-icon">搜</text>
        <input
          v-model="keyword"
          class="bank-page__search-input"
          type="text"
          placeholder="搜索想要练习的考试"
          placeholder-class="bank-page__search-placeholder"
          confirm-type="search"
          @confirm="handleSearch"
        />
        <text v-if="keyword" class="bank-page__search-clear" @tap="handleClear">×</text>
      </view>
      <text class="bank-page__search-btn" @tap="handleSearch">搜索</text>
    </view>

    <view class="bank-page__body">
      <!-- 左侧分类 -->
      <scroll-view scroll-y class="bank-page__side">
        <view
          v-for="category in bankStore.categories"
          :key="category.id"
          class="bank-page__side-item"
          :class="{ 'is-active': bankStore.activeCategoryId === category.id }"
          @tap="bankStore.switchCategory(category.id)"
        >
          <text>{{ category.name }}</text>
        </view>
      </scroll-view>

      <!-- 右侧题库列表 -->
      <scroll-view scroll-y class="bank-page__main" @scrolltolower="handleReachBottom">
        <view class="bank-page__main-header">
          <text class="bank-page__main-title">{{ activeCategoryName }}</text>
          <text class="bank-page__main-count">共 {{ total }} 个</text>
        </view>

        <template v-if="bankStore.banks.length">
          <bank-card v-for="bank in bankStore.banks" :key="bank.id" :bank="bank" @tap="goDetail(bank.id)">
            <template #action>
              <view class="bank-page__item-actions">
                <text class="bank-page__item-action" @tap.stop="handleRefresh(bank)">刷</text>
                <text class="bank-page__item-action" @tap.stop="handleMore(bank)">⋯</text>
              </view>
            </template>
          </bank-card>
        </template>

        <base-empty
          v-else-if="!bankStore.loading"
          title="没有找到题库"
          desc="换个关键词试试，或导入你自己的题目资料"
          icon-text="库"
        >
          <view class="bank-page__empty-btn" @tap="goImport">去导入题库</view>
        </base-empty>

        <list-load-more :loading="bankStore.loading" :has-more="bankStore.hasMore" />
      </scroll-view>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 题库页（P-02）
 * 结构：搜索 + 左侧分类 + 右侧我的题库列表（分页加载）
 * 接口：API-BANK-001 分类列表、API-BANK-002 我的题库列表
 */
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { useBankStore } from '@/stores/bank'
import { setStorage, STORAGE_KEYS } from '@/utils/storage'
import type { QuestionBank } from '@/types'

const bankStore = useBankStore()
const keyword = ref('')
const total = computed(() => bankStore.banks.length)

const activeCategoryName = computed(
  () => bankStore.categories.find((item) => item.id === bankStore.activeCategoryId)?.name ?? '我的题库'
)

onLoad(() => {
  bankStore.loadCategories()
  if (!bankStore.banks.length) bankStore.loadMyBanks(true)
})

function handleSearch() {
  if (keyword.value.trim()) {
    setStorage(STORAGE_KEYS.SEARCH_HISTORY, keyword.value.trim())
  }
  bankStore.loadMyBanks(true, keyword.value.trim())
}

function handleClear() {
  keyword.value = ''
  bankStore.loadMyBanks(true)
}

function handleReachBottom() {
  bankStore.loadMyBanks(false, keyword.value.trim())
}

function goDetail(id: number) {
  uni.navigateTo({ url: `/pages-sub/bank/detail?id=${id}` })
}

function goImport() {
  uni.navigateTo({ url: '/pages-sub/import/upload' })
}

function handleRefresh(bank: QuestionBank) {
  uni.showToast({ title: `已更新：${bank.title}`, icon: 'none' })
}

/** 卡片「⋯」菜单：重命名（API-BANK-005）/ 导出（暂无接口）/ 删除（API-BANK-006） */
function handleMore(bank: QuestionBank) {
  uni.showActionSheet({
    itemList: ['重命名', '导出题库', '删除题库'],
    success: ({ tapIndex }) => {
      if (tapIndex === 0) handleRename(bank)
      else if (tapIndex === 1) uni.showToast({ title: '导出功能待上线', icon: 'none' })
      else handleDelete(bank)
    }
  })
}

/** API-BANK-005 重命名题库：弹输入框 → 调接口 → 刷新列表 */
function handleRename(bank: QuestionBank) {
  uni.showModal({
    title: '重命名题库',
    editable: true,
    placeholderText: '请输入新的题库名称',
    content: bank.title,
    success: async (modal) => {
      const title = (modal.content ?? '').trim()
      if (!modal.confirm || !title || title === bank.title) return
      await updateBank(bank.id, { title })
      uni.showToast({ title: '重命名成功', icon: 'none' })
      bankStore.loadMyBanks(true, keyword.value.trim())
    }
  })
}

/** API-BANK-006 删除题库：二次确认 → 软删 → 刷新列表 */
function handleDelete(bank: QuestionBank) {
  uni.showModal({
    title: '删除题库',
    content: `确定删除题库「${bank.title}」吗？删除后可在回收站找回。`,
    confirmColor: '#EF4444',
    success: async (modal) => {
      if (!modal.confirm) return
      await deleteBank(bank.id)
      uni.showToast({ title: '已删除', icon: 'none' })
      bankStore.loadMyBanks(true, keyword.value.trim())
    }
  })
}
</script>

<style lang="scss" scoped>
.bank-page {
  height: 100vh;
  display: flex;
  flex-direction: column;
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
    background-color: $color-bg-page;
    border-radius: 34rpx;
    display: flex;
    align-items: center;
    padding: 0 $spacing-md;
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

  &__body {
    flex: 1;
    display: flex;
    overflow: hidden;
  }

  &__side {
    width: 200rpx;
    height: 100%;
    background-color: $color-bg-card;
  }

  &__side-item {
    padding: $spacing-md $spacing-sm;
    font-size: $font-size-sm;
    color: $color-text-regular;
    text-align: center;

    &.is-active {
      color: $color-primary;
      font-weight: 600;
      background-color: $color-primary-bg;
      border-left: 6rpx solid $color-primary;
    }
  }

  &__main {
    flex: 1;
    height: 100%;
    padding: $spacing-md;
  }

  &__main-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: $spacing-md;
  }

  &__main-title {
    font-size: $font-size-base;
    font-weight: 600;
    color: $color-text-primary;
  }

  &__main-count {
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__item-actions {
    display: flex;
    align-items: center;
  }

  &__item-action {
    width: 56rpx;
    height: 56rpx;
    margin-left: $spacing-xs;
    border-radius: $radius-circle;
    background-color: $color-bg-page;
    color: $color-text-secondary;
    font-size: $font-size-sm;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  &__empty-btn {
    margin-top: $spacing-lg;
    padding: 16rpx 40rpx;
    background-color: $color-primary;
    color: $color-text-inverse;
    font-size: $font-size-sm;
    border-radius: 36rpx;
  }
}
</style>
