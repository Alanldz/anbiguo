<template>
  <view class="answer">
    <nav-bar :title="navTitle" theme="light">
      <template #right>
        <text class="answer__nav-icon" @tap="showSheet = true">卡</text>
      </template>
    </nav-bar>

    <view class="answer__progress">
      <view class="answer__progress-bar" :style="{ width: `${progressPercent}%` }" />
      <text class="answer__progress-text">{{ currentIndex + 1 }} / {{ questions.length }}</text>
    </view>

    <scroll-view scroll-y class="answer__scroll">
      <view v-if="currentQuestion" class="answer__content">
        <question-card
          :question="currentQuestion"
          :index="currentIndex + 1"
          v-model="currentAnswer"
          :show-result="showResult"
        />

        <!-- 解析区 -->
        <view v-if="showResult" class="answer__analysis">
          <view class="answer__analysis-header">
            <text class="answer__analysis-result" :class="isCurrentCorrect ? 'is-correct' : 'is-wrong'">
              {{ isCurrentCorrect ? '回答正确' : '回答错误' }}
            </text>
            <text class="answer__analysis-answer">正确答案：{{ currentQuestion.answer }}</text>
          </view>
          <text class="answer__analysis-title">试题解析</text>
          <text class="answer__analysis-text">{{ currentQuestion.analysis }}</text>
        </view>

        <!-- 笔记区 -->
        <view v-if="currentQuestion.note" class="answer__note">
          <text class="answer__note-label">我的笔记</text>
          <text class="answer__note-text">{{ currentQuestion.note }}</text>
        </view>
      </view>
      <view v-else class="answer__loading">
        <text class="answer__loading-text">题目加载中…</text>
      </view>
    </scroll-view>

    <!-- 底部操作栏 -->
    <view class="answer__footer safe-bottom">
      <view class="answer__tools">
        <view class="answer__tool" @tap="handleFavorite">
          <text class="answer__tool-icon" :class="{ 'is-active': currentQuestion?.is_favorited }">藏</text>
          <text class="answer__tool-label">收藏</text>
        </view>
        <view class="answer__tool" @tap="handleNote">
          <text class="answer__tool-icon">记</text>
          <text class="answer__tool-label">笔记</text>
        </view>
        <view class="answer__tool" @tap="handleReport">
          <text class="answer__tool-icon">报</text>
          <text class="answer__tool-label">报错</text>
        </view>
      </view>

      <view class="answer__navigator">
        <view class="answer__btn answer__btn--plain" :class="{ 'is-disabled': currentIndex === 0 }" @tap="goPrev">
          上一题
        </view>
        <view v-if="!showResult" class="answer__btn answer__btn--primary" @tap="handleSubmit">提交答案</view>
        <view v-else class="answer__btn answer__btn--primary" @tap="goNext">
          {{ isLast ? '完成练习' : '下一题' }}
        </view>
      </view>
    </view>

    <!-- 答题卡弹层 -->
    <view v-if="showSheet" class="answer__sheet-mask" @tap="showSheet = false">
      <view @tap.stop>
        <answer-sheet :items="sheetItems" @select="handleSheetSelect" @close="showSheet = false">
          <template #footer>
            <view class="answer__sheet-submit" @tap="handleFinish">交卷并查看结果</view>
          </template>
        </answer-sheet>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 答题页（P-09）
 * 支持：顺序 / 专项 / 随机练习；单选、多选、判断；即时判分；收藏、笔记、报错
 * 接口：API-QUE-001 题目列表、API-QUE-002 提交作答、API-QUE-003 收藏、API-QUE-004 笔记
 */
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { fetchQuestions, saveNote, submitAnswer, toggleFavorite } from '@/api/question'
import type { AnswerSheetItem } from '@/components/answer-sheet/answer-sheet.vue'
import type { Question } from '@/types'

const MODE_LABEL: Record<string, string> = {
  sequence: '顺序练习',
  chapter: '专项练习',
  random: '随机练习'
}

const bankId = ref(0)
const mode = ref('sequence')
const questions = ref<Question[]>([])
const currentIndex = ref(0)
const answers = ref<Record<number, string>>({})
const answered = ref<Record<number, boolean>>({})
const correctMap = ref<Record<number, boolean>>({})
const showResult = ref(false)
const showSheet = ref(false)

const navTitle = computed(() => MODE_LABEL[mode.value] ?? '练习')
const currentQuestion = computed(() => questions.value[currentIndex.value] ?? null)
const isLast = computed(() => currentIndex.value === questions.value.length - 1)
const progressPercent = computed(() =>
  questions.value.length ? ((currentIndex.value + 1) / questions.value.length) * 100 : 0
)

/** 当前题作答（通过 v-model 双向绑定） */
const currentAnswer = computed({
  get: () => (currentQuestion.value ? answers.value[currentQuestion.value.id] ?? '' : ''),
  set: (value: string) => {
    if (!currentQuestion.value) return
    answers.value[currentQuestion.value.id] = value
    answered.value[currentQuestion.value.id] = true
  }
})

const isCurrentCorrect = computed(() => {
  if (!currentQuestion.value) return false
  return currentAnswer.value === currentQuestion.value.answer
})

const sheetItems = computed<AnswerSheetItem[]>(() =>
  questions.value.map((question, index) => ({
    index,
    answered: Boolean(answered.value[question.id]),
    correct: question.id in correctMap.value ? correctMap.value[question.id] : undefined
  }))
)

onLoad(async (options) => {
  bankId.value = Number(options?.bank_id ?? 0)
  mode.value = options?.mode ?? 'sequence'
  const result = await fetchQuestions(bankId.value, {
    page: 1,
    page_size: 20,
    mode: mode.value as 'sequence' | 'random' | 'chapter'
  })
  questions.value = result.list
})

async function handleSubmit() {
  if (!currentQuestion.value) return
  if (!currentAnswer.value) {
    uni.showToast({ title: '请先作答', icon: 'none' })
    return
  }
  const result = await submitAnswer({
    question_id: currentQuestion.value.id,
    answer: currentAnswer.value
  })
  correctMap.value[currentQuestion.value.id] = result.correct
  showResult.value = true
}

function goPrev() {
  if (currentIndex.value === 0) return
  currentIndex.value -= 1
  syncResultState()
}

function goNext() {
  if (isLast.value) {
    handleFinish()
    return
  }
  currentIndex.value += 1
  syncResultState()
}

/** 切换到已作答题目时同步展示解析 */
function syncResultState() {
  const question = currentQuestion.value
  showResult.value = Boolean(question && question.id in correctMap.value)
}

function handleSheetSelect(index: number) {
  currentIndex.value = index
  showSheet.value = false
  syncResultState()
}

function handleFinish() {
  showSheet.value = false
  const correctCount = Object.values(correctMap.value).filter(Boolean).length
  uni.showModal({
    title: '练习完成',
    content: `已答 ${Object.keys(answered.value).length} 题，答对 ${correctCount} 题。错题已自动加入错题本。`,
    showCancel: false,
    success: () => uni.navigateBack()
  })
}

async function handleFavorite() {
  if (!currentQuestion.value) return
  const next = !currentQuestion.value.is_favorited
  await toggleFavorite(currentQuestion.value.id, next)
  currentQuestion.value.is_favorited = next
  uni.showToast({ title: next ? '已收藏' : '已取消收藏', icon: 'none' })
}

function handleNote() {
  if (!currentQuestion.value) return
  uni.showModal({
    title: '我的笔记',
    editable: true,
    placeholderText: '记录你的理解与记忆技巧',
    success: async ({ confirm, content }) => {
      if (!confirm || !currentQuestion.value) return
      await saveNote(currentQuestion.value.id, content ?? '')
      currentQuestion.value.note = content ?? ''
      uni.showToast({ title: '笔记已保存', icon: 'none' })
    }
  })
}

function handleReport() {
  if (!currentQuestion.value) return
  uni.showActionSheet({
    itemList: ['题干有误', '答案有误', '解析错误', '其他问题'],
    success: () => uni.showToast({ title: '已提交报错，感谢反馈', icon: 'none' })
  })
}
</script>

<style lang="scss" scoped>
.answer {
  height: 100vh;
  display: flex;
  flex-direction: column;
  background-color: $color-bg-page;

  &__nav-icon {
    font-size: $font-size-sm;
    color: $color-text-regular;
  }

  &__progress {
    position: relative;
    height: 48rpx;
    background-color: $color-border;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  &__progress-bar {
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    background-color: $color-primary-light;
    opacity: 0.3;
    transition: width 0.2s;
  }

  &__progress-text {
    position: relative;
    font-size: $font-size-xs;
    color: $color-text-regular;
  }

  &__scroll {
    flex: 1;
  }

  &__content {
    padding: $spacing-lg;
  }

  &__analysis {
    margin-top: $spacing-md;
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

  &__analysis-result {
    font-size: $font-size-base;
    font-weight: 600;

    &.is-correct {
      color: $color-success;
    }
    &.is-wrong {
      color: $color-danger;
    }
  }

  &__analysis-answer {
    font-size: $font-size-sm;
    color: $color-text-secondary;
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

  &__note {
    margin-top: $spacing-md;
    padding: $spacing-md;
    border-radius: $radius-lg;
    background-color: $color-warning-bg;
  }

  &__note-label {
    display: block;
    font-size: $font-size-xs;
    color: $color-warning;
    margin-bottom: 4rpx;
  }

  &__note-text {
    font-size: $font-size-sm;
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

  &__footer {
    background-color: $color-bg-card;
    border-top: 1rpx solid $color-divider;
  }

  &__tools {
    display: flex;
    padding: $spacing-sm 0;
    border-bottom: 1rpx solid $color-divider;
  }

  &__tool {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  &__tool-icon {
    font-size: $font-size-md;
    color: $color-text-secondary;

    &.is-active {
      color: $color-warning;
    }
  }

  &__tool-label {
    margin-top: 2rpx;
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__navigator {
    display: flex;
    padding: $spacing-md $spacing-lg;
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

      &.is-disabled {
        opacity: 0.4;
      }
    }

    &--primary {
      background: $color-primary-gradient;
      color: $color-text-inverse;
      font-weight: 600;
    }
  }

  &__sheet-mask {
    position: fixed;
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;
    background-color: $color-bg-mask;
    display: flex;
    align-items: flex-end;
    z-index: 200;
  }

  &__sheet-submit {
    height: 84rpx;
    border-radius: 42rpx;
    background: $color-primary-gradient;
    color: $color-text-inverse;
    font-size: $font-size-base;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
  }
}
</style>
