<template>
  <view class="wrong">
    <view class="wrong__tabs">
      <view
        v-for="tab in tabs"
        :key="tab.key"
        class="wrong__tab"
        :class="{ 'is-active': activeTab === tab.key }"
        @tap="switchTab(tab.key)"
      >
        <text>{{ tab.label }}</text>
      </view>
    </view>

    <view class="wrong__list">
      <template v-if="list.length">
        <view v-for="(item, index) in list" :key="item.id" class="wrong__item" @tap="goDetail(index)">
          <view class="wrong__item-header">
            <text class="wrong__item-type">{{ typeLabel(item.type) }}</text>
            <text class="wrong__item-index">第 {{ index + 1 }} 题</text>
          </view>
          <text class="wrong__item-title text-ellipsis-2">{{ item.title }}</text>
          <view class="wrong__item-footer">
            <text class="wrong__item-answer">正确答案：{{ item.answer }}</text>
            <text class="wrong__item-remove" @tap.stop="handleRemove(item.id)">移除</text>
          </view>
        </view>
      </template>

      <base-empty
        v-else-if="!loading"
        title="暂无错题"
        desc="练习中答错的题目会自动收录到这里，方便集中攻克"
        icon-text="错"
      />
      <list-load-more :loading="loading" :has-more="false" :show-no-more="false" />
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 错题本（P-19）
 * 接口：API-WRG-001 错题列表、API-WRG-002 移除错题
 */
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { fetchWrongQuestions, removeWrongQuestion } from '@/api/question'
import { QuestionType, type Question } from '@/types'

const tabs = [
  { key: 'all', label: '全部错题' },
  { key: 'single', label: '单选' },
  { key: 'multiple', label: '多选' },
  { key: 'judge', label: '判断' }
]

const activeTab = ref('all')
const list = ref<Question[]>([])
const loading = ref(false)
const bankId = ref<number>()

const TYPE_LABEL: Record<number, string> = {
  [QuestionType.Single]: '单选',
  [QuestionType.Multiple]: '多选',
  [QuestionType.Judge]: '判断',
  [QuestionType.Blank]: '填空',
  [QuestionType.Essay]: '简答'
}

function typeLabel(type: QuestionType): string {
  return TYPE_LABEL[type] ?? '题目'
}

onLoad((options) => {
  bankId.value = options?.bank_id ? Number(options.bank_id) : undefined
  loadList()
})

async function loadList() {
  loading.value = true
  try {
    const typeMap: Record<string, QuestionType | undefined> = {
      all: undefined,
      single: QuestionType.Single,
      multiple: QuestionType.Multiple,
      judge: QuestionType.Judge
    }
    const result = await fetchWrongQuestions({
      page: 1,
      page_size: 20,
      bank_id: bankId.value,
      question_type: typeMap[activeTab.value]
    })
    list.value = result.list
  } finally {
    loading.value = false
  }
}

function switchTab(key: string) {
  activeTab.value = key
  loadList()
}

function goDetail(index: number) {
  uni.navigateTo({ url: `/pages-sub/practice/answer?mode=sequence&index=${index}` })
}

async function handleRemove(id: number) {
  await removeWrongQuestion(id)
  list.value = list.value.filter((item) => item.id !== id)
  uni.showToast({ title: '已移除', icon: 'none' })
}
</script>

<style lang="scss" scoped>
.wrong {
  min-height: 100vh;
  background-color: $color-bg-page;

  &__tabs {
    display: flex;
    background-color: $color-bg-card;
    padding: 0 $spacing-lg;
  }

  &__tab {
    flex: 1;
    text-align: center;
    padding: $spacing-md 0;
    font-size: $font-size-base;
    color: $color-text-secondary;

    &.is-active {
      color: $color-primary;
      font-weight: 600;
      border-bottom: 4rpx solid $color-primary;
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
  }

  &__item-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: $spacing-sm;
  }

  &__item-type {
    font-size: $font-size-xs;
    color: $color-danger;
    background-color: $color-danger-bg;
    padding: 2rpx 10rpx;
    border-radius: $radius-sm;
  }

  &__item-index {
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__item-title {
    font-size: $font-size-base;
    line-height: 1.6;
    color: $color-text-primary;
  }

  &__item-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: $spacing-sm;
  }

  &__item-answer {
    font-size: $font-size-xs;
    color: $color-success;
  }

  &__item-remove {
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }
}
</style>
