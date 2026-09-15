<template>
  <view class="paper">
    <!-- 顶部：倒计时 + 进度 -->
    <view class="paper__header">
      <view class="paper__timer" :class="{ 'is-danger': remainSeconds <= 300 }">
        <text class="paper__timer-label">剩余时间</text>
        <text class="paper__timer-text">{{ countdownText }}</text>
      </view>
      <view class="paper__progress">
        <view class="paper__progress-bar" :style="{ width: `${progressPercent}%` }" />
        <text class="paper__progress-text">{{ currentIndex + 1 }} / {{ questions.length }}</text>
      </view>
    </view>

    <scroll-view scroll-y class="paper__scroll">
      <view v-if="currentQuestion" class="paper__content">
        <!-- 选择/判断题：复用题目卡片 -->
        <question-card
          v-if="currentQuestion.type !== QuestionType.Blank && currentQuestion.type !== QuestionType.Essay"
          :question="currentQuestion"
          :index="currentIndex + 1"
          v-model="currentAnswer"
        />

        <!-- 填空/简答题：题干 + 文本框 -->
        <view v-else class="paper__text-question">
          <view class="paper__text-header">
            <text class="paper__text-type">{{ typeLabel(currentQuestion.type) }}</text>
            <text class="paper__text-index">第 {{ currentIndex + 1 }} 题</text>
          </view>
          <text class="paper__text-title">{{ currentQuestion.title }}</text>
          <textarea
            class="paper__textarea"
            v-model="currentAnswer"
            :placeholder="currentQuestion.type === QuestionType.Blank ? '请输入填空答案' : '请输入答案要点'"
            :maxlength="500"
          />
        </view>
      </view>

      <view v-else class="paper__loading">
        <text class="paper__loading-text">试卷加载中…</text>
      </view>
    </scroll-view>

    <!-- 底部操作栏 -->
    <view class="paper__footer safe-bottom">
      <view class="paper__btn paper__btn--plain" :class="{ 'is-disabled': currentIndex === 0 }" @tap="goPrev">
        上一题
      </view>
      <view
        v-if="!isLast"
        class="paper__btn paper__btn--plain"
        @tap="goNext"
      >
        下一题
      </view>
      <view class="paper__btn paper__btn--primary" @tap="handleConfirmSubmit">交卷</view>
      <view class="paper__btn paper__btn--sheet" @tap="showSheet = true">卡</view>
    </view>

    <!-- 答题卡弹层 -->
    <view v-if="showSheet" class="paper__sheet-mask" @tap="showSheet = false">
      <view @tap.stop>
        <answer-sheet :items="sheetItems" @select="handleSheetSelect" @close="showSheet = false">
          <template #footer>
            <view class="paper__sheet-submit" @tap="handleConfirmSubmit">交卷并查看成绩</view>
          </template>
        </answer-sheet>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 考试答题（P-12）
 * 接口：API-EXM-002 试卷详情（GET /exam-papers/{id}）、API-EXM-003 交卷（POST /exam-records，幂等）
 * 说明：顶部倒计时（duration_minutes），剩 5 分钟高亮、到时自动交卷；
 *       交卷二次确认（提示未答题数），交卷 loading 锁防重复提交；
 *       成功后跳转成绩单 exam/record?id=记录id。
 *       单选/多选/判断作答交互复用 question-card（参照 practice/answer.vue）。
 */
import { computed, ref } from 'vue'
import { onLoad, onUnload } from '@dcloudio/uni-app'
import { fetchExamPaper, submitExam } from '@/api/exam'
import { QuestionType, type ExamPaper, type Question } from '@/types'
import type { AnswerSheetItem } from '@/components/answer-sheet/answer-sheet.vue'

const paper = ref<ExamPaper | null>(null)
const questions = ref<Question[]>([])
const currentIndex = ref(0)
/** 各题作答：question_id → 答案（多选为连续字母串，填空/简答为文本） */
const answers = ref<Record<number, string>>({})
const answered = computed(() => Object.keys(answers.value).filter((key) => answers.value[Number(key)]))

const remainSeconds = ref(0)
const costSeconds = ref(0)
const showSheet = ref(false)
const submitting = ref(false)
let timer: ReturnType<typeof setInterval> | null = null

const currentQuestion = computed(() => questions.value[currentIndex.value] ?? null)
const isLast = computed(() => currentIndex.value === questions.value.length - 1)
const progressPercent = computed(() =>
  questions.value.length ? ((currentIndex.value + 1) / questions.value.length) * 100 : 0
)

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

/** 当前题作答（v-model 双向绑定） */
const currentAnswer = computed({
  get: () => (currentQuestion.value ? answers.value[currentQuestion.value.id] ?? '' : ''),
  set: (value: string) => {
    if (!currentQuestion.value) return
    answers.value[currentQuestion.value.id] = value
  }
})

const countdownText = computed(() => {
  const total = Math.max(0, remainSeconds.value)
  const mm = String(Math.floor(total / 60)).padStart(2, '0')
  const ss = String(total % 60).padStart(2, '0')
  return `${mm}:${ss}`
})

const sheetItems = computed<AnswerSheetItem[]>(() =>
  questions.value.map((question, index) => ({
    index,
    answered: Boolean(answers.value[question.id])
  }))
)

onLoad(async (options) => {
  const id = Number(options?.id ?? 0)
  if (!id) {
    uni.showToast({ title: '试卷不存在', icon: 'none' })
    return
  }
  paper.value = await fetchExamPaper(id)
  questions.value = paper.value.questions ?? []
  remainSeconds.value = paper.value.duration_minutes * 60
  startTimer()
})

onUnload(stopTimer)

/** 启动倒计时：每秒 costSeconds+1、remainSeconds-1，到时自动交卷 */
function startTimer() {
  stopTimer()
  timer = setInterval(() => {
    costSeconds.value += 1
    remainSeconds.value -= 1
    if (remainSeconds.value <= 0) {
      stopTimer()
      uni.showToast({ title: '考试时间到，自动交卷', icon: 'none' })
      doSubmit()
    }
  }, 1000)
}

function stopTimer() {
  if (timer) {
    clearInterval(timer)
    timer = null
  }
}

function goPrev() {
  if (currentIndex.value === 0) return
  currentIndex.value -= 1
}

function goNext() {
  if (isLast.value) return
  currentIndex.value += 1
}

function handleSheetSelect(index: number) {
  currentIndex.value = index
  showSheet.value = false
}

/** 交卷前二次确认：提示未答题数 */
function handleConfirmSubmit() {
  showSheet.value = false
  const unanswered = questions.value.length - answered.value.length
  uni.showModal({
    title: '确认交卷',
    content: unanswered
      ? `还有 ${unanswered} 题未作答，确认交卷吗？交卷后不可修改。`
      : '已答完全部题目，确认交卷吗？',
    success: ({ confirm }) => {
      if (confirm) doSubmit()
    }
  })
}

/** 执行交卷：loading 锁防重复点击；EXM-003 后端幂等 */
async function doSubmit() {
  if (submitting.value) return
  submitting.value = true
  stopTimer()
  try {
    const record = await submitExam({
      paper_id: paper.value?.id ?? 0,
      answers: questions.value
        .filter((question) => answers.value[question.id])
        .map((question) => ({ question_id: question.id, answer: answers.value[question.id] })),
      cost_seconds: costSeconds.value
    })
    // 埋点：交卷成功
    track('exam_submit', { biz_type: 'exam_paper', biz_id: paper.value?.id ?? 0 })
    uni.redirectTo({ url: `/pages-sub/exam/record?id=${record.id}` })
  } catch {
    // 交卷失败恢复计时，允许重试
    submitting.value = false
    startTimer()
    uni.showToast({ title: '交卷失败，请重试', icon: 'none' })
  }
}
</script>

<style lang="scss" scoped>
.paper {
  height: 100vh;
  display: flex;
  flex-direction: column;
  background-color: $color-bg-page;

  &__header {
    display: flex;
    align-items: center;
    padding: $spacing-sm $spacing-lg;
    background-color: $color-bg-card;
    border-bottom: 1rpx solid $color-divider;
  }

  &__timer {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 8rpx 20rpx;
    border-radius: $radius-md;
    background-color: $color-primary-bg;

    &.is-danger {
      background-color: $color-danger-bg;

      .paper__timer-text {
        color: $color-danger;
      }
    }
  }

  &__timer-label {
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__timer-text {
    font-size: $font-size-lg;
    font-weight: 700;
    color: $color-primary;
    font-variant-numeric: tabular-nums;
  }

  &__progress {
    position: relative;
    flex: 1;
    height: 48rpx;
    margin-left: $spacing-md;
    background-color: $color-divider;
    border-radius: 24rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
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

  &__text-question {
    background-color: $color-bg-card;
    border-radius: $radius-lg;
    padding: $spacing-lg;
    box-shadow: $shadow-sm;
  }

  &__text-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: $spacing-md;
  }

  &__text-type {
    font-size: $font-size-xs;
    color: $color-primary;
    background-color: $color-primary-bg;
    padding: 4rpx 12rpx;
    border-radius: $radius-sm;
  }

  &__text-index {
    font-size: $font-size-sm;
    color: $color-text-secondary;
  }

  &__text-title {
    display: block;
    font-size: $font-size-md;
    line-height: 1.7;
    color: $color-text-primary;
    margin-bottom: $spacing-md;
  }

  &__textarea {
    width: 100%;
    height: 220rpx;
    padding: $spacing-md;
    box-sizing: border-box;
    background-color: $color-bg-page;
    border-radius: $radius-md;
    font-size: $font-size-base;
    line-height: 1.6;
    color: $color-text-primary;
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
    display: flex;
    align-items: center;
    padding: $spacing-md $spacing-lg;
    background-color: $color-bg-card;
    border-top: 1rpx solid $color-divider;
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
      margin-right: $spacing-sm;
      background: $color-primary-gradient;
      color: $color-text-inverse;
      font-weight: 600;
    }

    &--sheet {
      flex: 0 0 84rpx;
      border: 2rpx solid $color-border;
      color: $color-text-secondary;
      font-size: $font-size-sm;
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
