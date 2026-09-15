<template>
  <view class="notice">
    <!-- Tab 筛选 + 全部已读 -->
    <view class="notice__toolbar">
      <view class="notice__tabs">
        <view
          v-for="tab in tabs"
          :key="tab.key"
          class="notice__tab"
          :class="{ 'is-active': activeTab === tab.key }"
          @tap="handleTabChange(tab.key)"
        >
          {{ tab.label }}
        </view>
      </view>
      <text class="notice__read-all" @tap="handleReadAll">全部已读</text>
    </view>

    <view class="notice__list">
      <template v-if="list.length">
        <view
          v-for="item in list"
          :key="item.id"
          class="notice__item"
          :class="{ 'is-read': item.is_read === 1 }"
          @tap="handleRead(item)"
        >
          <view class="notice__item-header">
            <view class="notice__item-title-wrap">
              <view v-if="item.is_read === 0" class="notice__dot" />
              <text class="notice__title">{{ item.title }}</text>
            </view>
            <text class="notice__type" :class="typeClass(item.type)">{{ typeLabel(item.type) }}</text>
          </view>
          <text class="notice__content">{{ item.content }}</text>
          <text class="notice__time">{{ item.created_at }}</text>
        </view>
      </template>

      <base-empty
        v-else-if="!loading"
        title="暂无消息"
        desc="系统通知、互动与业务消息都会出现在这里"
        icon-text="信"
      />

      <list-load-more :loading="loading" :has-more="hasMore" />
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 消息中心（P-28）
 * 接口：API-MSG-001 通知列表、API-MSG-003 标记已读、API-MSG-004 全部已读
 * 说明：
 *  - Tab：全部 / 未读 / 系统 / 互动 / 业务（系统=type 1，互动=type 2，业务=type 3）；
 *  - 点击未读卡片立即本地置为已读，并调用 markRead 同步；
 *  - 「全部已读」二次确认，成功提示标记条数。
 */
import { ref } from 'vue'
import { onReachBottom } from '@dcloudio/uni-app'
import { fetchNotifications, markAllRead, markRead } from '@/api/notification'
import { NotificationType, type NotificationItem } from '@/types'

type TabKey = 'all' | 'unread' | 'system' | 'interaction' | 'business'

const tabs: { key: TabKey; label: string }[] = [
  { key: 'all', label: '全部' },
  { key: 'unread', label: '未读' },
  { key: 'system', label: '系统' },
  { key: 'interaction', label: '互动' },
  { key: 'business', label: '业务' }
]

const TYPE_LABEL: Record<number, string> = {
  [NotificationType.System]: '系统',
  [NotificationType.Interaction]: '互动',
  [NotificationType.Business]: '业务'
}

const list = ref<NotificationItem[]>([])
const loading = ref(false)
const hasMore = ref(true)
const page = ref(1)
const pageSize = 20
const activeTab = ref<TabKey>('all')

function typeLabel(type: NotificationType): string {
  return TYPE_LABEL[type] ?? '通知'
}

function typeClass(type: NotificationType): string {
  if (type === NotificationType.Interaction) return 'is-interaction'
  if (type === NotificationType.Business) return 'is-business'
  return ''
}

/** 当前 Tab 对应的接口筛选参数 */
function currentQuery() {
  switch (activeTab.value) {
    case 'unread':
      return { is_read: 0 as const }
    case 'system':
      return { type: NotificationType.System }
    case 'interaction':
      return { type: NotificationType.Interaction }
    case 'business':
      return { type: NotificationType.Business }
    default:
      return {}
  }
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
    const res = await fetchNotifications({
      page: page.value,
      page_size: pageSize,
      ...currentQuery()
    })
    list.value = reset ? res.list : [...list.value, ...res.list]
    page.value += 1
    hasMore.value = res.pagination.page < res.pagination.total_pages
  } finally {
    loading.value = false
  }
}

onReachBottom(() => loadData(false))

function handleTabChange(key: TabKey) {
  if (activeTab.value === key) return
  activeTab.value = key
  loadData(true)
}

/** 点击卡片：未读则本地立即置为已读并同步接口 */
function handleRead(item: NotificationItem) {
  if (item.is_read === 1) return
  item.is_read = 1
  markRead(item.id).catch(() => {
    // 失败回滚为未读
    item.is_read = 0
  })
}

function handleReadAll() {
  uni.showModal({
    title: '全部已读',
    content: '确定将所有未读消息标记为已读吗？',
    success: async (modal) => {
      if (!modal.confirm) return
      const res = await markAllRead()
      list.value = list.value.map((item) => ({ ...item, is_read: 1 as const }))
      uni.showToast({ title: `已标记 ${res.marked} 条`, icon: 'none' })
    }
  })
}

loadData(true)
</script>

<style lang="scss" scoped>
.notice {
  min-height: 100vh;
  background-color: $color-bg-page;

  &__toolbar {
    display: flex;
    align-items: center;
    padding: $spacing-sm $spacing-lg;
    background-color: $color-bg-card;
    position: sticky;
    top: 0;
    z-index: 10;
  }

  &__tabs {
    flex: 1;
    display: flex;
    align-items: center;
  }

  &__tab {
    margin-right: $spacing-md;
    padding: 8rpx 20rpx;
    font-size: $font-size-sm;
    color: $color-text-secondary;
    border-radius: 28rpx;
    background-color: $color-bg-page;

    &.is-active {
      color: $color-primary;
      background-color: $color-primary-bg;
      font-weight: 600;
    }
  }

  &__read-all {
    flex-shrink: 0;
    font-size: $font-size-sm;
    color: $color-primary;
  }

  &__list {
    padding: $spacing-lg;
  }

  &__item {
    padding: $spacing-md;
    margin-bottom: $spacing-sm;
    background-color: $color-bg-card;
    border-radius: $radius-lg;

    // 已读卡片弱化显示
    &.is-read {
      opacity: 0.72;
    }
  }

  &__item-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: $spacing-xs;
  }

  &__item-title-wrap {
    flex: 1;
    display: flex;
    align-items: center;
    min-width: 0;
  }

  &__dot {
    flex-shrink: 0;
    width: 14rpx;
    height: 14rpx;
    margin-right: 10rpx;
    border-radius: $radius-circle;
    background-color: $color-danger;
  }

  &__title {
    font-size: $font-size-base;
    font-weight: 600;
    color: $color-text-primary;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  &__type {
    flex-shrink: 0;
    margin-left: $spacing-sm;
    font-size: $font-size-xs;
    color: $color-text-secondary;
    background-color: $color-bg-page;
    padding: 2rpx 10rpx;
    border-radius: $radius-sm;

    &.is-interaction {
      color: $color-warning;
      background-color: $color-warning-bg;
    }

    &.is-business {
      color: $color-primary;
      background-color: $color-primary-bg;
    }
  }

  &__content {
    display: block;
    font-size: $font-size-sm;
    line-height: 1.6;
    color: $color-text-regular;
  }

  &__time {
    display: block;
    margin-top: $spacing-xs;
    font-size: $font-size-xs;
    color: $color-text-placeholder;
  }
}
</style>
