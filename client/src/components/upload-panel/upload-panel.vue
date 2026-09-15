<template>
  <view class="upload-panel">
    <view class="upload-panel__body" @tap="handleChoose">
      <view class="upload-panel__icon">
        <text class="upload-panel__icon-text">＋</text>
      </view>
      <text class="upload-panel__hint">{{ hint }}</text>
      <text class="upload-panel__sub">{{ subHint }}</text>
    </view>

    <view v-if="files.length" class="upload-panel__list">
      <view v-for="(file, index) in files" :key="file.objectKey" class="upload-panel__item">
        <view class="upload-panel__file">
          <text class="upload-panel__file-ext">{{ file.ext.toUpperCase() }}</text>
          <view class="upload-panel__file-main">
            <text class="upload-panel__file-name text-ellipsis">{{ file.originName }}</text>
            <view class="upload-panel__progress">
              <view class="upload-panel__progress-bar" :style="{ width: `${file.progress}%` }" />
            </view>
          </view>
          <text class="upload-panel__file-state">{{ stateLabel(file.status) }}</text>
        </view>
        <text class="upload-panel__remove" @tap.stop="handleRemove(index)">删除</text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 上传面板
 * 流程：获取上传凭证（API-FIL-001）→ 直传七牛 → 回调登记（API-FIL-002）
 * 详见 docs/05-OSS存储与文件分类规范.md §四
 */
export type UploadFileStatus = 'pending' | 'uploading' | 'success' | 'failed'

export interface UploadFile {
  originName: string
  objectKey: string
  ext: string
  size: number
  progress: number
  status: UploadFileStatus
}

interface Props {
  files: UploadFile[]
  /** 允许的扩展名（不含点），如 ['docx', 'xlsx', 'pdf'] */
  accept?: string[]
  hint?: string
  subHint?: string
}

const props = withDefaults(defineProps<Props>(), {
  accept: () => ['doc', 'docx', 'xls', 'xlsx', 'pdf', 'txt', 'jpg', 'jpeg', 'png'],
  hint: '点击上传文件',
  subHint: '支持 Word / Excel / PDF / TXT / 图片'
})

const emit = defineEmits<{
  (e: 'choose', files: Array<{ path: string; name: string; size: number }>): void
  (e: 'remove', index: number): void
}>()

const STATUS_LABEL: Record<UploadFileStatus, string> = {
  pending: '待上传',
  uploading: '上传中',
  success: '已完成',
  failed: '失败'
}

function stateLabel(status: UploadFileStatus): string {
  return STATUS_LABEL[status]
}

/** 多端文件选择：小程序用 chooseMessageFile，App/H5 用 chooseFile */
function handleChoose() {
  // #ifdef MP-WEIXIN
  uni.chooseMessageFile({
    count: 10,
    type: 'file',
    extension: props.accept,
    success: (res) => {
      emit(
        'choose',
        res.tempFiles.map((item) => ({ path: item.path, name: item.name, size: item.size }))
      )
    }
  })
  // #endif

  // #ifndef MP-WEIXIN
  uni.chooseFile({
    count: 10,
    extension: props.accept,
    success: (res) => {
      const files = res.tempFiles as Array<{ path: string; name: string; size: number }>
      emit('choose', files.map((item) => ({ path: item.path, name: item.name, size: item.size })))
    },
    fail: () => {
      uni.showToast({ title: '当前环境暂不支持选择文件', icon: 'none' })
    }
  })
  // #endif
}

function handleRemove(index: number) {
  emit('remove', index)
}
</script>

<style lang="scss" scoped>
.upload-panel {
  background-color: $color-bg-card;
  border-radius: $radius-lg;
  padding: $spacing-md;
  border: 2rpx dashed $color-border;

  &__body {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: $spacing-xl 0;
    background-color: $color-bg-page;
    border-radius: $radius-md;
  }

  &__icon {
    width: 96rpx;
    height: 96rpx;
    border-radius: $radius-circle;
    background-color: $color-primary;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: $spacing-md;
  }

  &__icon-text {
    font-size: 48rpx;
    color: $color-text-inverse;
    line-height: 1;
  }

  &__hint {
    font-size: $font-size-base;
    color: $color-text-primary;
    font-weight: 500;
  }

  &__sub {
    margin-top: $spacing-xs;
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__list {
    margin-top: $spacing-md;
  }

  &__item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: $spacing-sm 0;
    border-bottom: 1rpx solid $color-divider;
  }

  &__file {
    flex: 1;
    display: flex;
    align-items: center;
    min-width: 0;
  }

  &__file-ext {
    font-size: $font-size-xs;
    color: $color-primary;
    background-color: $color-primary-bg;
    padding: 4rpx 10rpx;
    border-radius: $radius-sm;
    margin-right: $spacing-sm;
  }

  &__file-main {
    flex: 1;
    min-width: 0;
  }

  &__file-name {
    font-size: $font-size-sm;
    color: $color-text-primary;
  }

  &__progress {
    height: 6rpx;
    background-color: $color-divider;
    border-radius: 3rpx;
    margin-top: $spacing-xs;
    overflow: hidden;
  }

  &__progress-bar {
    height: 100%;
    background-color: $color-primary;
  }

  &__file-state {
    font-size: $font-size-xs;
    color: $color-text-secondary;
    margin-left: $spacing-sm;
  }

  &__remove {
    font-size: $font-size-xs;
    color: $color-danger;
    margin-left: $spacing-md;
  }
}
</style>
