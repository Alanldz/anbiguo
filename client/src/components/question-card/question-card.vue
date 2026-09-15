<template>
  <view class="question-card">
    <view class="question-card__header">
      <text class="question-card__type">{{ typeLabel }}</text>
      <text class="question-card__index">第 {{ index }} 题</text>
    </view>

    <view class="question-card__title">
      <text>{{ question.title }}</text>
    </view>

    <view class="question-card__options">
      <view
        v-for="option in question.options"
        :key="option.key"
        class="question-card__option"
        :class="optionClass(option.key)"
        @tap="handleSelect(option.key)"
      >
        <view class="question-card__option-key" :class="optionClass(option.key)">
          <text>{{ option.key }}</text>
        </view>
        <text class="question-card__option-content">{{ option.content }}</text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 题目卡片（答题主体）
 * 支持单选 / 多选 / 判断；是否展示对错由 showResult 控制
 */
import { computed } from 'vue'
import { QuestionType, type Question } from '@/types'

interface Props {
  question: Question
  index: number
  /** 用户已选答案，多选以逗号或连续字符串形式传入 */
  modelValue?: string
  /** 是否已展示结果（交卷后或答题即判模式） */
  showResult?: boolean
  /** 是否为背题模式（直接展示答案） */
  reciteMode?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  showResult: false,
  reciteMode: false
})

const emit = defineEmits<{ (e: 'update:modelValue', value: string): void }>()

const TYPE_LABEL: Record<number, string> = {
  [QuestionType.Single]: '单选题',
  [QuestionType.Multiple]: '多选题',
  [QuestionType.Judge]: '判断题',
  [QuestionType.Blank]: '填空题',
  [QuestionType.Essay]: '简答题'
}

const typeLabel = computed(() => TYPE_LABEL[props.question.type] ?? '题目')
const isMultiple = computed(() => props.question.type === QuestionType.Multiple)

/** 判断某选项是否被选中 */
function isSelected(key: string): boolean {
  return props.modelValue.includes(key)
}

/** 选项样式：结果模式下正确答案标绿、错选标红 */
function optionClass(key: string): string[] {
  const classes: string[] = []
  if (!props.showResult && !props.reciteMode) {
    return isSelected(key) ? ['is-selected'] : []
  }
  const correct = props.question.answer.includes(key)
  if (correct) classes.push('is-correct')
  if (!correct && isSelected(key)) classes.push('is-wrong')
  return classes
}

function handleSelect(key: string) {
  if (props.showResult && !props.reciteMode) return

  if (isMultiple.value) {
    const selected = props.modelValue.split('').filter(Boolean)
    const next = selected.includes(key) ? selected.filter((k) => k !== key) : [...selected, key]
    emit('update:modelValue', next.sort().join(''))
    return
  }
  emit('update:modelValue', key)
}
</script>

<style lang="scss" scoped>
.question-card {
  background-color: $color-bg-card;
  border-radius: $radius-lg;
  padding: $spacing-lg;
  box-shadow: $shadow-sm;

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: $spacing-md;
  }

  &__type {
    font-size: $font-size-xs;
    color: $color-primary;
    background-color: $color-primary-bg;
    padding: 4rpx 12rpx;
    border-radius: $radius-sm;
  }

  &__index {
    font-size: $font-size-sm;
    color: $color-text-secondary;
  }

  &__title {
    font-size: $font-size-md;
    line-height: 1.7;
    color: $color-text-primary;
    margin-bottom: $spacing-lg;
  }

  &__option {
    display: flex;
    align-items: flex-start;
    padding: $spacing-md;
    border: 2rpx solid $color-border;
    border-radius: $radius-md;
    margin-bottom: $spacing-sm;

    &.is-selected {
      border-color: $color-primary;
      background-color: $color-primary-bg;
    }

    &.is-correct {
      border-color: $color-success;
      background-color: $color-success-bg;
    }

    &.is-wrong {
      border-color: $color-danger;
      background-color: $color-danger-bg;
    }
  }

  &__option-key {
    width: 44rpx;
    height: 44rpx;
    border-radius: $radius-circle;
    border: 2rpx solid $color-border;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: $spacing-sm;
    flex-shrink: 0;
    font-size: $font-size-sm;
    color: $color-text-secondary;

    &.is-selected {
      border-color: $color-primary;
      background-color: $color-primary;
      color: $color-text-inverse;
    }

    &.is-correct {
      border-color: $color-success;
      background-color: $color-success;
      color: $color-text-inverse;
    }

    &.is-wrong {
      border-color: $color-danger;
      background-color: $color-danger;
      color: $color-text-inverse;
    }
  }

  &__option-content {
    flex: 1;
    font-size: $font-size-base;
    line-height: 1.6;
    color: $color-text-regular;
  }
}
</style>
