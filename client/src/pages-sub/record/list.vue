<template>
  <view class="record">
    <!-- 筛选栏 -->
    <view class="record__filter">
      <picker class="record__picker" :range="bankOptions" range-key="title" @change="onBankChange">
        <view class="record__picker-inner">
          <text class="record__picker-text">{{ currentBankName }}</text>
          <text class="record__picker-arrow">▾</text>
        </view>
      </picker>
      <picker class="record__picker" :range="statusOptions" range-key="label" @change="onStatusChange">
        <view class="record__picker-inner">
          <text class="record__picker-text">{{ currentStatusLabel }}</text>
          <text class="record__picker-arrow">▾</text>
        </view>
      </picker>
    </view>

    <view class="record__list">
      <template v-if="list.length">
        <view v-for="item in list" :key="item.id" class="record__item">
          <view class="record__item-header">
            <text class="record__bank text-ellipsis">{{ item.bank_name }}</text>
            <text class="record__status" :class="statusClass(item.status)">{{ statusLabel(item.status) }}</text>
          </view>
          <view class="record__item-tags">
            <text class="record__mode">{{ modeLabel(item.practice_mode) }}</text>
            <text class="record__time">{{ formatTime(item.started_at) }}</text>
          </view>
          <view class="record__metrics">
            <view class="record__metric">
              <text class="record__metric-value">{{ item.answered_count }}/{{ item.total_count }}</text>
              <text class="record__metric-label">进度</text>
            </view>
            <view class="record__metric">
              <text class="record__metric-value">{{ item.correct_rate }}%</text>
              <text class="record__metric-label">正确率</text>
            </view>
            <view class="record__metric">
              <text class="record__metric-value">{{ formatDuration(item.duration_seconds) }}</text>
              <text class="record__metric-label">时长</text>
            </view>
          </view>
          <view v-if="item.status === PracticeStatus.Ongoing" class="record__continue" @tap="goContinue(item)">
            继续练习
          </view>
        </view>
      </template>

      <base-empty
        v-else-if="!loading"
        title="还没有练习记录"
        desc="去题库挑一份题目开始练习，记录会自动留在这里"
        icon-text="录"
      />

      <list-load-more :loading="loading" :has-more="hasMore" />
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 练习记录（P-25）
 * 接口：API-REC-001 练习记录列表
 * 说明：进行中记录显示「继续练习」按钮跳练习答题页；时长格式化为 mm:ss。
 */
import { computed, onMounted, ref } from 'vue'
import { onReachBottom } from '@dcloudio/uni-app'
import { fetchPracticeRecords } from '@/api/record'
import { fetchMyBanks } from '@/api/bank'
import { formatDuration } from '@/utils/format'
import { PracticeMode, PracticeStatus, type PracticeRecordItem, type QuestionBank } from '@/types'

const list = ref<PracticeRecordItem[]>([])
const loading = ref(false)
const hasMore = ref(true)
const page = ref(1)
const pageSize = 20
const bankId = ref<number>()
const statusFilter = ref<PracticeStatus>()

const bankOptions = ref<QuestionBank[]>([])

const MODE_LABEL: Record<number, string> = {
  [PracticeMode.Sequence]: '顺序练习',
  [PracticeMode.Random]: '随机练习',
  [PracticeMode.Special]: '专项练习',
  [PracticeMode.Wrong]: '错题重做',
  [PracticeMode.Flashcard]: '闪卡',
  [PracticeMode.Behead]: '斩题'
}

const STATUS_LABEL: Record<number, string> = {
  [PracticeStatus.Ongoing]: '进行中',
  [PracticeStatus.Finished]: '已完成',
  [PracticeStatus.Abandoned]: '已放弃'
}

const STATUS_CLASS: Record<number, string> = {
  [PracticeStatus.Ongoing]: 'is-ongoing',
  [PracticeStatus.Finished]: 'is-finished',
  [PracticeStatus.Abandoned]: 'is-abandoned'
}

/** 状态筛选下拉项（首项为全部） */
const statusOptions = [
  { label: '全部状态', value: undefined as PracticeStatus | undefined },
  { label: '进行中', value: PracticeStatus.Ongoing },
  { label: '已完成', value: PracticeStatus.Finished },
  { label: '已放弃', value: PracticeStatus.Abandoned }
]

const currentBankName = computed(() => {
  if (!bankId.value) return '全部题库'
  return bankOptions.value.find((b) => b.id === bankId.value)?.title ?? '全部题库'
})

const currentStatusLabel = computed(() => {
  if (statusFilter.value === undefined) return '全部状态'
  return STATUS_LABEL[statusFilter.value] ?? '全部状态'
})

function modeLabel(mode: PracticeMode): string {
  return MODE_LABEL[mode] ?? '练习'
}

function statusLabel(status: PracticeStatus): string {
  return STATUS_LABEL[status] ?? '未知'
}

function statusClass(status: PracticeStatus): string {
  return STATUS_CLASS[status] ?? ''
}

function formatTime(value: string | null): string {
  return value ?? '未开始'
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
    const res = await fetchPracticeRecords({
      page: page.value,
      page_size: pageSize,
      bank_id: bankId.value,
      status: statusFilter.value
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

function onStatusChange(e: { detail: { value: number } }) {
  const option = statusOptions[e.detail.value]
  statusFilter.value = option?.value
  loadData(true)
}

function goContinue(item: PracticeRecordItem) {
  const mode = item.practice_mode === PracticeMode.Random ? 'random' : 'sequence'
  uni.navigateTo({
    url: `/pages-sub/practice/answer?bank_id=${item.bank_id}&mode=${mode}`
  })
}
</script>

<style lang="scss" scoped>
.record {
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
    flex: 1;

    &:first-child {
      margin-right: $spacing-sm;
    }
  }

  &__picker-inner {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 68rpx;
    background-color: $color-bg-page;
    border-radius: 34rpx;
  }

  &__picker-text {
    font-size: $font-size-sm;
    color: $color-text-primary;
    max-width: 220rpx;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  &__picker-arrow {
    margin-left: 6rpx;
    font-size: $font-size-xs;
    color: $color-text-secondary;
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

  &__item-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: $spacing-sm;
  }

  &__bank {
    flex: 1;
    font-size: $font-size-base;
    font-weight: 600;
    color: $color-text-primary;
    min-width: 0;
  }

  &__status {
    flex-shrink: 0;
    font-size: $font-size-xs;
    padding: 2rpx 12rpx;
    border-radius: $radius-sm;
    margin-left: $spacing-sm;

    &.is-ongoing {
      color: $color-primary;
      background-color: $color-primary-bg;
    }

    &.is-finished {
      color: $color-success;
      background-color: $color-success-bg;
    }

    &.is-abandoned {
      color: $color-text-secondary;
      background-color: $color-bg-page;
    }
  }

  &__item-tags {
    display: flex;
    align-items: center;
    margin-bottom: $spacing-md;
  }

  &__mode {
    font-size: $font-size-xs;
    color: $color-warning;
    background-color: $color-warning-bg;
    padding: 2rpx 10rpx;
    border-radius: $radius-sm;
  }

  &__time {
    margin-left: auto;
    font-size: $font-size-xs;
    color: $color-text-placeholder;
  }

  &__metrics {
    display: flex;
    border-radius: $radius-md;
    overflow: hidden;
  }

  &__metric {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: $spacing-sm 0;
    background-color: $color-bg-page;
  }

  &__metric-value {
    font-size: $font-size-base;
    font-weight: 600;
    color: $color-text-primary;
  }

  &__metric-label {
    margin-top: 4rpx;
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__continue {
    margin-top: $spacing-md;
    height: 72rpx;
    border-radius: 36rpx;
    background-color: $color-primary;
    color: $color-text-inverse;
    font-size: $font-size-base;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
  }
}
</style>
