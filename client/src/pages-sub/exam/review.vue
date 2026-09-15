<template>
  <view class="review">
    <!-- 筛选：全部 / 只看错题 -->
    <view class="review__tabs">
      <view
        v-for="tab in tabs"
        :key="tab.key"
        class="review__tab"
        :class="{ 'is-active': activeTab === tab.key }"
        @tap="activeTab = tab.key"
      >
        <text>{{ tab.label }}</text>
      </view>
    </view>

    <view v-if="record" class="review__list">
      <template v-if="visibleItems.length">
        <view
          v-for="(item, index) in visibleItems"
          :key="item.question.id"
          class="review__item"
        >
          <view class="review__item-header">
            <text class="review__item-type">{{ typeLabel(item.question.type) }}</text>
            <text
              class="review__item-result"
              :class="item.isCorrect ? 'is-correct' : 'is-wrong'"
            >
              {{ item.isCorrect ? '答对' : '答错' }}
            </text>
            <text class="review__item-index">第 {{ item.number }} 题</text>
          </view>

          <!-- 复用题目卡片展示题干/选项，showResult 模式下正确答案标绿、错选标红 -->
          <question-card
            :question="item.question"
            :index="item.number"
            :model-value="item.userAnswer"
            :show-result="true"
          />

          <!-- 我的答案 / 正确答案 / 解析 -->
          <view class="review__analysis">
            <view class="review__analysis-header">
              <text class="review__analysis-mine">我的答案：{{ item.userAnswer || '未作答' }}</text>
              <text class="review__analysis-answer">正确答案：{{ item.question.answer }}</text>
            </view>
            <text class="review__analysis-title">试题解析</text>
            <text class="review__analysis-text">{{ item.question.analysis }}</text>
          </view>
        </view>
      </template>

      <base-empty
        v-else
        title="太棒了，全部答对"
        desc="本次考试没有错题，继续保持"
        icon-text="赞"
      />
    </view>

    <view v-else class="review__loading">
      <text class="review__loading-text">试卷回顾加载中…</text>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 试卷回顾（P-14）
 * 接口：API-EXM-004 成绩详情（GET /exam-records/{id}）+ API-EXM-002 试卷详情（GET /exam-papers/{id}）
 * 说明：EXM-004 返回逐题作答明细（user_answer/is_correct），与试卷题目合并后
 *       逐题展示题干 / 我的答案 / 正确答案 / 解析与对错标记；
 *       解析展示风格参照 wrong/list 与 practice/answer。
 */
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { fetchExamPaper, fetchExamRecord } from '@/api/exam'
import { QuestionType, type ExamPaper, type ExamRecordDetail, type Question } from '@/types'

const tabs = [
  { key: 'all', label: '全部题目' },
  { key: 'wrong', label: '只看错题' }
]

const activeTab = ref('all')
const record = ref<ExamRecordDetail | null>(null)
const paper = ref<ExamPaper | null>(null)

interface ReviewItem {
  question: Question
  /** 试卷内题号（1 起） */
  number: number
  userAnswer: string
  isCorrect: boolean
}

const TYPE_LABEL: Record<number, string> = {
  [QuestionType.Single]: '单选题',
  [QuestionType.Multiple]: '多选题',
  [QuestionType.Judge]: '判断题',
  [QuestionType.Blank]: '填空题',
  [QuestionType.Essay]: '简答题'
}

function typeLabel(type: QuestionType): string {
  return TYPE_LABEL[type] ?? '题目'
}

/** 试卷题目与作答明细合并（按 question_id 关联） */
const items = computed<ReviewItem[]>(() => {
  if (!record.value || !paper.value) return []
  const answers = new Map(record.value.answers.map((item) => [item.question_id, item]))
  return (paper.value.questions ?? []).map((question, index) => {
    const answer = answers.get(question.id)
    return {
      question,
      number: index + 1,
      userAnswer: answer?.user_answer && answer.user_answer !== '未作答' ? answer.user_answer : '',
      isCorrect: Boolean(answer?.is_correct)
    }
  })
})

const visibleItems = computed(() =>
  activeTab.value === 'wrong' ? items.value.filter((item) => !item.isCorrect) : items.value
)

onLoad(async (options) => {
  const id = Number(options?.id ?? 0)
  if (!id) {
    uni.showToast({ title: '记录不存在', icon: 'none' })
    return
  }
  // 先取成绩（含 paper_id 与作答明细），再取试卷题目
  record.value = await fetchExamRecord(id)
  paper.value = await fetchExamPaper(record.value.paper_id)
})
</script>

<style lang="scss" scoped>
.review {
  min-height: 100vh;
  background-color: $color-bg-page;

  &__tabs {
    display: flex;
    background-color: $color-bg-card;
    padding: 0 $spacing-lg;
    position: sticky;
    top: 0;
    z-index: 10;
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
    margin-bottom: $spacing-md;
  }

  &__item-header {
    display: flex;
    align-items: center;
    margin-bottom: $spacing-sm;
  }

  &__item-type {
    font-size: $font-size-xs;
    color: $color-primary;
    background-color: $color-primary-bg;
    padding: 2rpx 10rpx;
    border-radius: $radius-sm;
  }

  &__item-result {
    margin-left: $spacing-sm;
    font-size: $font-size-xs;
    padding: 2rpx 10rpx;
    border-radius: $radius-sm;

    &.is-correct {
      color: $color-success;
      background-color: $color-success-bg;
    }

    &.is-wrong {
      color: $color-danger;
      background-color: $color-danger-bg;
    }
  }

  &__item-index {
    margin-left: auto;
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__analysis {
    margin-top: $spacing-sm;
    padding: $spacing-lg;
    background-color: $color-bg-card;
    border-radius: $radius-lg;
  }

  &__analysis-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: $spacing-md;
  }

  &__analysis-mine {
    font-size: $font-size-sm;
    color: $color-danger;
  }

  &__analysis-answer {
    font-size: $font-size-sm;
    color: $color-success;
  }

  &__analysis-title {
    display: block;
    font-size: $font-size-sm;
    font-weight: 600;
    color: $color-text-primary;
    margin-bottom: $spacing-xs;
  }

  &__analysis-text {
    font-size: $font-size-sm;
    line-height: 1.7;
    color: $color-text-regular;
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
