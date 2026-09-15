<template>
  <view class="answer-sheet">
    <view class="answer-sheet__header">
      <text class="answer-sheet__title">答题卡</text>
      <text class="answer-sheet__close" @tap="emit('close')">收起</text>
    </view>

    <view class="answer-sheet__legend">
      <view class="answer-sheet__legend-item">
        <view class="answer-sheet__dot answer-sheet__dot--done" />
        <text>已答</text>
      </view>
      <view class="answer-sheet__legend-item">
        <view class="answer-sheet__dot answer-sheet__dot--todo" />
        <text>未答</text>
      </view>
      <view class="answer-sheet__legend-item">
        <view class="answer-sheet__dot answer-sheet__dot--wrong" />
        <text>错误</text>
      </view>
    </view>

    <scroll-view scroll-y class="answer-sheet__grid">
      <view class="answer-sheet__grid-inner">
        <view
          v-for="item in items"
          :key="item.index"
          class="answer-sheet__cell"
          :class="cellClass(item)"
          @tap="emit('select', item.index)"
        >
          <text>{{ item.index + 1 }}</text>
        </view>
      </view>
    </scroll-view>

    <view class="answer-sheet__footer">
      <slot name="footer" />
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 答题卡（题号定位面板）
 * 复用于：练习答题页、考试答题页
 */
export interface AnswerSheetItem {
  index: number
  answered: boolean
  correct?: boolean
}

interface Props {
  items: AnswerSheetItem[]
}

defineProps<Props>()
const emit = defineEmits<{
  (e: 'select', index: number): void
  (e: 'close'): void
}>()

function cellClass(item: AnswerSheetItem): string[] {
  if (item.correct === false) return ['is-wrong']
  return item.answered ? ['is-done'] : ['is-todo']
}
</script>

<style lang="scss" scoped>
.answer-sheet {
  background-color: $color-bg-card;
  border-radius: $radius-xl $radius-xl 0 0;
  padding: $spacing-lg $spacing-lg $spacing-xl;
  max-height: 70vh;
  display: flex;
  flex-direction: column;

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: $spacing-md;
  }

  &__title {
    font-size: $font-size-lg;
    font-weight: 600;
  }

  &__close {
    font-size: $font-size-sm;
    color: $color-text-secondary;
  }

  &__legend {
    display: flex;
    align-items: center;
    margin-bottom: $spacing-md;
  }

  &__legend-item {
    display: flex;
    align-items: center;
    font-size: $font-size-xs;
    color: $color-text-secondary;
    margin-right: $spacing-lg;
  }

  &__dot {
    width: 20rpx;
    height: 20rpx;
    border-radius: $radius-circle;
    margin-right: $spacing-xs;

    &--done {
      background-color: $color-primary;
    }
    &--todo {
      border: 2rpx solid $color-border;
    }
    &--wrong {
      background-color: $color-danger;
    }
  }

  &__grid {
    flex: 1;
    max-height: 40vh;
  }

  &__grid-inner {
    display: flex;
    flex-wrap: wrap;
  }

  &__cell {
    width: 80rpx;
    height: 80rpx;
    margin: 0 $spacing-sm $spacing-sm 0;
    border-radius: $radius-md;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: $font-size-sm;
    color: $color-text-regular;
    background-color: $color-bg-page;

    &.is-done {
      background-color: $color-primary;
      color: $color-text-inverse;
    }

    &.is-wrong {
      background-color: $color-danger;
      color: $color-text-inverse;
    }
  }

  &__footer {
    margin-top: $spacing-md;
  }
}
</style>
