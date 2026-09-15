<template>
  <view class="task">
    <view v-if="task" class="task__card">
      <text class="task__file text-ellipsis">{{ task.origin_name }}</text>

      <!-- 状态标签 -->
      <view class="task__status-row">
        <text class="task__status" :class="`is-${task.status}`">{{ statusText }}</text>
        <text v-if="task.total_count" class="task__count">共 {{ task.total_count }} 题</text>
      </view>

      <!-- 进度条（解析中/完成展示） -->
      <view v-if="task.status === 'parsing' || task.status === 'pending' || isDoneLike" class="task__progress-wrap">
        <view class="task__progress">
          <view class="task__progress-bar" :style="{ width: `${progressPercent}%` }" />
        </view>
        <view class="task__progress-meta">
          <text class="task__progress-label">{{ progressPercent }}%</text>
          <text v-if="task.total_count" class="task__progress-label">
            已解析 {{ task.parsed_count }} / {{ task.total_count }}
          </text>
        </view>
      </view>

      <!-- 待校对说明（后端占位返回，AI 解析待接入） -->
      <view v-if="task.status === 'proofread'" class="task__notice task__notice--info">
        <text class="task__notice-title">AI 解析待接入</text>
        <text class="task__notice-text">
          当前版本暂不自动生成题目，任务已进入「待校对」状态。AI 文档解析将在后续版本开放，届时可在此逐题校对识别结果。
        </text>
      </view>

      <!-- 失败原因 -->
      <view v-if="task.status === 'failed'" class="task__notice task__notice--error">
        <text class="task__notice-title">解析失败</text>
        <text class="task__notice-text">{{ task.fail_reason || '解析过程中出现异常，请重试' }}</text>
      </view>

      <!-- 成功说明 -->
      <view v-if="task.status === 'success'" class="task__notice task__notice--success">
        <text class="task__notice-title">解析完成</text>
        <text class="task__notice-text">题目已导入题库，可以去题库详情开始练习了。</text>
      </view>

      <!-- 操作按钮 -->
      <view class="task__actions">
        <view class="task__btn task__btn--plain" @tap="reload">刷新进度</view>
        <view
          v-if="task.status === 'success'"
          class="task__btn task__btn--primary"
          @tap="goBank"
        >
          查看题库
        </view>
        <view v-else class="task__btn task__btn--primary" @tap="goUpload">重新上传</view>
      </view>
    </view>

    <view v-else class="task__loading">
      <text class="task__loading-text">任务加载中…</text>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 解析进度与校对（P-18）
 * 接口：API-IMP-002 查询解析进度（GET /import/tasks/{id}）
 * 说明：异步任务前端轮询（2s），解析中/待处理状态下自动刷新，onUnload 清理；
 *       后端本期为同步占位，返回「待校对」状态时如实展示并说明 AI 解析待接入；
 *       失败展示 fail_reason。
 */
import { computed, ref } from 'vue'
import { onLoad, onUnload } from '@dcloudio/uni-app'
import { fetchImportTask } from '@/api/importer'
import type { ImportTask } from '@/types'

const task = ref<ImportTask | null>(null)
let pollTimer: ReturnType<typeof setInterval> | null = null
const taskId = ref(0)

/** success / proofread / failed 视为终态 */
const isDoneLike = computed(() =>
  ['success', 'proofread', 'failed'].includes(task.value?.status ?? '')
)

const STATUS_TEXT: Record<string, string> = {
  pending: '排队中',
  parsing: '解析中',
  proofread: '待校对',
  success: '解析完成',
  failed: '解析失败'
}

const statusText = computed(
  () => task.value?.status_text ?? STATUS_TEXT[task.value?.status ?? ''] ?? '处理中'
)

/** 进度：优先取后端 progress，否则按 parsed/total 推算，终态兜底 */
const progressPercent = computed(() => {
  if (!task.value) return 0
  if (typeof task.value.progress === 'number') {
    return Math.min(100, Math.max(0, Math.round(task.value.progress)))
  }
  if (task.value.total_count) {
    return Math.min(100, Math.round((task.value.parsed_count / task.value.total_count) * 100))
  }
  return isDoneLike.value ? 100 : 0
})

onLoad((options) => {
  taskId.value = Number(options?.id ?? 0)
  if (!taskId.value) {
    uni.showToast({ title: '任务不存在', icon: 'none' })
    return
  }
  loadTask()
  startPolling()
})

onUnload(stopPolling)

async function loadTask() {
  task.value = await fetchImportTask(taskId.value)
}

/** 解析中每 2 秒轮询一次，终态停止 */
function startPolling() {
  stopPolling()
  pollTimer = setInterval(() => {
    if (isDoneLike.value) {
      stopPolling()
      return
    }
    loadTask()
  }, 2000)
}

function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

function reload() {
  loadTask()
}

function goBank() {
  uni.navigateTo({ url: `/pages-sub/bank/detail?id=${task.value?.bank_id ?? 0}` })
}

function goUpload() {
  uni.redirectTo({ url: '/pages-sub/import/upload' })
}
</script>

<style lang="scss" scoped>
.task {
  min-height: 100vh;
  padding: $spacing-lg;
  background-color: $color-bg-page;

  &__card {
    padding: $spacing-lg;
    background-color: $color-bg-card;
    border-radius: $radius-xl;
    box-shadow: $shadow-sm;
  }

  &__file {
    display: block;
    font-size: $font-size-lg;
    font-weight: 700;
    color: $color-text-primary;
  }

  &__status-row {
    display: flex;
    align-items: center;
    margin-top: $spacing-md;
  }

  &__status {
    font-size: $font-size-xs;
    padding: 4rpx 16rpx;
    border-radius: $radius-sm;

    &.is-parsing,
    &.is-pending {
      color: $color-primary;
      background-color: $color-primary-bg;
    }

    &.is-proofread {
      color: $color-warning;
      background-color: $color-warning-bg;
    }

    &.is-success {
      color: $color-success;
      background-color: $color-success-bg;
    }

    &.is-failed {
      color: $color-danger;
      background-color: $color-danger-bg;
    }
  }

  &__count {
    margin-left: auto;
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__progress-wrap {
    margin-top: $spacing-lg;
  }

  &__progress {
    height: 20rpx;
    border-radius: 10rpx;
    background-color: $color-divider;
    overflow: hidden;
  }

  &__progress-bar {
    height: 100%;
    border-radius: 10rpx;
    background: $color-primary-gradient;
    transition: width 0.3s;
  }

  &__progress-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: $spacing-xs;
  }

  &__progress-label {
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__notice {
    margin-top: $spacing-lg;
    padding: $spacing-md;
    border-radius: $radius-md;

    &--info {
      background-color: $color-warning-bg;
    }

    &--error {
      background-color: $color-danger-bg;
    }

    &--success {
      background-color: $color-success-bg;
    }
  }

  &__notice-title {
    display: block;
    font-size: $font-size-sm;
    font-weight: 600;
    color: $color-text-primary;
    margin-bottom: $spacing-xs;
  }

  &__notice-text {
    font-size: $font-size-sm;
    line-height: 1.7;
    color: $color-text-regular;
  }

  &__actions {
    display: flex;
    margin-top: $spacing-lg;
  }

  &__btn {
    flex: 1;
    height: 84rpx;
    border-radius: 42rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: $font-size-base;

    &--plain {
      margin-right: $spacing-sm;
      border: 2rpx solid $color-border;
      color: $color-text-regular;
    }

    &--primary {
      background: $color-primary-gradient;
      color: $color-text-inverse;
      font-weight: 600;
    }
  }

  &__loading {
    padding: $spacing-xl * 2;
    text-align: center;
  }

  &__loading-text {
    font-size: $font-size-sm;
    color: $color-text-placeholder;
  }
}
</style>
