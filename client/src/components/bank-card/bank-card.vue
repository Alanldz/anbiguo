<template>
  <view class="bank-card" @tap="handleTap">
    <view class="bank-card__icon">
      <text class="bank-card__icon-text">{{ iconText }}</text>
    </view>

    <view class="bank-card__main">
      <view class="bank-card__title text-ellipsis">{{ bank.title }}</view>
      <view class="bank-card__meta">
        <text class="bank-card__tag" :class="`bank-card__tag--${sourceType}`">{{ sourceLabel }}</text>
        <text class="bank-card__count">{{ bank.question_count }}道</text>
        <text class="bank-card__date">{{ bank.created_at }}</text>
      </view>
    </view>

    <view class="bank-card__action">
      <slot name="action">
        <text class="bank-card__arrow">›</text>
      </slot>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 题库卡片
 * 复用于：首页「我的学习空间」、题库页我的题库列表、题库市场
 */
import { computed } from 'vue'
import { BankSourceType, type QuestionBank } from '@/types'

interface Props {
  bank: QuestionBank
}

const props = defineProps<Props>()
const emit = defineEmits<{ (e: 'tap', bank: QuestionBank): void }>()

/** 来源标签文案，与 docs/03 §三 BankSourceType 枚举一致 */
const SOURCE_LABEL: Record<number, string> = {
  [BankSourceType.Upload]: '上传',
  [BankSourceType.Official]: '官方',
  [BankSourceType.Purchased]: '购买',
  [BankSourceType.AiGenerated]: 'AI生成'
}

const sourceLabel = computed(() => SOURCE_LABEL[props.bank.source_type] ?? '未知')
const sourceType = computed(() => props.bank.source_type)
/** 取题库名首字作为图标占位，接入 UI 后替换为题库封面 */
const iconText = computed(() => props.bank.title.slice(0, 1))

function handleTap() {
  emit('tap', props.bank)
}
</script>

<style lang="scss" scoped>
.bank-card {
  display: flex;
  align-items: center;
  padding: $spacing-md;
  background-color: $color-bg-card;
  border-radius: $radius-lg;
  margin-bottom: $spacing-sm;
  box-shadow: $shadow-sm;

  &__icon {
    width: 72rpx;
    height: 72rpx;
    border-radius: $radius-md;
    background-color: $color-primary-bg;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: $spacing-md;
    flex-shrink: 0;
  }

  &__icon-text {
    font-size: $font-size-lg;
    color: $color-primary;
    font-weight: 600;
  }

  &__main {
    flex: 1;
    min-width: 0;
  }

  &__title {
    font-size: $font-size-base;
    font-weight: 500;
    color: $color-text-primary;
  }

  &__meta {
    display: flex;
    align-items: center;
    margin-top: $spacing-xs;
  }

  &__tag {
    font-size: $font-size-xs;
    padding: 2rpx 10rpx;
    border-radius: $radius-sm;
    margin-right: $spacing-sm;
    color: $color-primary;
    background-color: $color-primary-bg;

    &--2 {
      color: $color-success;
      background-color: $color-success-bg;
    }
    &--3 {
      color: $color-warning;
      background-color: $color-warning-bg;
    }
    &--4 {
      color: #8b5cf6;
      background-color: #f3eefe;
    }
  }

  &__count,
  &__date {
    font-size: $font-size-xs;
    color: $color-text-secondary;
    margin-right: $spacing-sm;
  }

  &__action {
    flex-shrink: 0;
    margin-left: $spacing-sm;
  }

  &__arrow {
    font-size: 40rpx;
    color: $color-text-placeholder;
    line-height: 1;
  }
}
</style>
