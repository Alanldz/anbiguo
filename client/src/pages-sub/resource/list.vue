<template>
  <view class="resource">
    <view class="resource__list">
      <template v-if="list.length">
        <view v-for="item in list" :key="item.id" class="resource__item">
          <view class="resource__icon" :class="`is-${iconType(item.file_type)}`">
            {{ iconText(item.file_type) }}
          </view>
          <view class="resource__info">
            <text class="resource__name text-ellipsis">{{ item.file_name }}</text>
            <text class="resource__meta">{{ formatSize(item.file_size) }} · {{ item.created_at }}</text>
          </view>
          <text class="resource__action" @tap="handleDownload(item)">{{ actionText(item) }}</text>
        </view>
      </template>

      <base-empty
        v-else-if="!loading"
        title="暂无学习资料"
        desc="可在题库详情页上传文档 / 讲义 / 音视频"
        icon-text="资"
      />

      <list-load-more :loading="loading" :has-more="hasMore" />
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 学习资料（P-26）
 * 接口：API-FIL-003 学习资料列表（GET /file-assets，biz_type=3）
 * 入口参数：bank_id（可选，限定题库下的资料）
 * 说明：Mock 演示环境下下载/预览按钮提示「演示环境暂不支持下载」；
 *       真实模式下有 url 走预览，无地址提示功能即将开放。
 */
import { ref } from 'vue'
import { onLoad, onReachBottom } from '@dcloudio/uni-app'
import { USE_MOCK } from '@/api/config'
import { fetchResources } from '@/api/resource'
import type { ResourceItem } from '@/types'

const list = ref<ResourceItem[]>([])
const loading = ref(false)
const hasMore = ref(true)
const page = ref(1)
const pageSize = 20
const bankId = ref(0)

onLoad((options) => {
  bankId.value = Number(options?.bank_id ?? 0)
})

onReachBottom(() => loadData(false))

/** 图标分组：文档 / 表格 / 音视频 / 图片 / 其他 */
function iconType(ext: string): string {
  if (['doc', 'docx', 'pdf', 'txt'].includes(ext)) return 'doc'
  if (['xls', 'xlsx', 'csv'].includes(ext)) return 'sheet'
  if (['mp4', 'mp3', 'wav', 'avi', 'mov', 'm4a'].includes(ext)) return 'media'
  if (['png', 'jpg', 'jpeg', 'gif', 'webp'].includes(ext)) return 'image'
  return 'other'
}

function iconText(ext: string): string {
  const map: Record<string, string> = { doc: '文', sheet: '表', media: '媒', image: '图', other: '件' }
  return map[iconType(ext)] ?? '件'
}

function actionText(item: ResourceItem): string {
  if (['mp4', 'mp3', 'wav', 'm4a'].includes(item.file_type)) return '预览'
  return '下载'
}

/** 文件大小格式化：B / KB / MB / GB */
function formatSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  if (bytes < 1024 * 1024 * 1024) return `${(bytes / 1024 / 1024).toFixed(1)} MB`
  return `${(bytes / 1024 / 1024 / 1024).toFixed(2)} GB`
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
    const res = await fetchResources({
      page: page.value,
      page_size: pageSize,
      bank_id: bankId.value || undefined
    })
    list.value = reset ? res.list : [...list.value, ...res.list]
    page.value += 1
    hasMore.value = res.pagination.page < res.pagination.total_pages
  } finally {
    loading.value = false
  }
}

function handleDownload(item: ResourceItem) {
  if (USE_MOCK || !item.url) {
    uni.showToast({ title: USE_MOCK ? '演示环境暂不支持下载' : '下载功能即将开放', icon: 'none' })
    return
  }
  // 真实模式：下载后用系统预览打开（私密文件 url 为服务端签发的临时地址）
  uni.showLoading({ title: '下载中' })
  uni.downloadFile({
    url: item.url,
    success: (res) => {
      uni.openDocument({
        filePath: res.tempFilePath,
        showMenu: true,
        fail: () => uni.showToast({ title: '该文件类型暂不支持在线预览', icon: 'none' })
      })
    },
    fail: () => uni.showToast({ title: '下载失败，请稍后重试', icon: 'none' }),
    complete: () => uni.hideLoading()
  })
}
</script>

<style lang="scss" scoped>
.resource {
  min-height: 100vh;
  background-color: $color-bg-page;

  &__list {
    padding: $spacing-lg;
  }

  &__item {
    display: flex;
    align-items: center;
    padding: $spacing-md;
    margin-bottom: $spacing-sm;
    background-color: $color-bg-card;
    border-radius: $radius-lg;
    box-shadow: $shadow-sm;
  }

  &__icon {
    flex-shrink: 0;
    width: 80rpx;
    height: 80rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: $radius-lg;
    font-size: $font-size-base;
    font-weight: 600;

    &.is-doc {
      color: #2b7cff;
      background-color: #eaf2ff;
    }

    &.is-sheet {
      color: #22c55e;
      background-color: #e8f8ee;
    }

    &.is-media {
      color: #8b5cf6;
      background-color: #f3eeff;
    }

    &.is-image {
      color: #f59e0b;
      background-color: #fff5e6;
    }

    &.is-other {
      color: $color-text-secondary;
      background-color: $color-bg-page;
    }
  }

  &__info {
    flex: 1;
    min-width: 0;
    margin: 0 $spacing-md;
  }

  &__name {
    display: block;
    font-size: $font-size-base;
    color: $color-text-primary;
    font-weight: 500;
  }

  &__meta {
    display: block;
    margin-top: 6rpx;
    font-size: $font-size-xs;
    color: $color-text-placeholder;
  }

  &__action {
    flex-shrink: 0;
    font-size: $font-size-xs;
    color: $color-primary;
    border: 2rpx solid $color-primary;
    padding: 8rpx 24rpx;
    border-radius: 28rpx;
  }
}
</style>
