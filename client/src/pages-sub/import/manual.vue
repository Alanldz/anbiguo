<template>
  <view class="manual">
    <view class="manual__card">
      <!-- 题库选择 -->
      <view class="manual__field">
        <text class="manual__label">所属题库</text>
        <picker :range="bankTitles" @change="onBankChange">
          <view class="manual__picker">
            <text class="manual__picker-text">{{ currentBankTitle }}</text>
            <text class="manual__picker-arrow">▾</text>
          </view>
        </picker>
      </view>

      <!-- 题型选择 -->
      <view class="manual__field">
        <text class="manual__label">题型</text>
        <view class="manual__types">
          <view
            v-for="item in TYPE_ITEMS"
            :key="item.value"
            class="manual__type"
            :class="{ 'is-active': type === item.value }"
            @tap="switchType(item.value)"
          >
            <text>{{ item.label }}</text>
          </view>
        </view>
      </view>

      <!-- 题干 -->
      <view class="manual__field">
        <text class="manual__label">题干</text>
        <textarea
          class="manual__textarea"
          v-model="title"
          placeholder="请输入题目内容"
          :maxlength="1000"
        />
      </view>

      <!-- 选项（单选/多选动态增删；判断固定两项；填空/简答隐藏） -->
      <view v-if="type === QuestionType.Single || type === QuestionType.Multiple" class="manual__field">
        <text class="manual__label">选项（{{ options.length }} 项）</text>
        <view v-for="(option, index) in options" :key="index" class="manual__option">
          <text class="manual__option-key">{{ option.key }}</text>
          <input
            v-model="option.content"
            class="manual__option-input"
            type="text"
            :placeholder="`请输入选项 ${option.key} 内容`"
          />
          <text
            v-if="options.length > 2"
            class="manual__option-remove"
            @tap="removeOption(index)"
          >×</text>
        </view>
        <view v-if="options.length < 8" class="manual__option-add" @tap="addOption">+ 添加选项</view>
      </view>

      <!-- 判断题固定选项展示 -->
      <view v-if="type === QuestionType.Judge" class="manual__field">
        <text class="manual__label">选项（判断题固定 正确 / 错误）</text>
        <view v-for="option in options" :key="option.key" class="manual__option is-fixed">
          <text class="manual__option-key">{{ option.key }}</text>
          <text class="manual__option-content">{{ option.content }}</text>
        </view>
      </view>

      <!-- 正确答案 -->
      <view class="manual__field">
        <text class="manual__label">正确答案</text>
        <!-- 选择/判断：点选 -->
        <view v-if="type === QuestionType.Single || type === QuestionType.Judge" class="manual__answers">
          <view
            v-for="option in options"
            :key="option.key"
            class="manual__answer"
            :class="{ 'is-active': answer === option.key }"
            @tap="answer = option.key"
          >
            <text>{{ option.key }}</text>
          </view>
        </view>
        <!-- 多选：点选可多选 -->
        <view v-else-if="type === QuestionType.Multiple" class="manual__answers">
          <view
            v-for="option in options"
            :key="option.key"
            class="manual__answer"
            :class="{ 'is-active': answer.includes(option.key) }"
            @tap="toggleMultiAnswer(option.key)"
          >
            <text>{{ option.key }}</text>
          </view>
        </view>
        <!-- 填空/简答：文本输入 -->
        <input
          v-else
          v-model="answer"
          class="manual__answer-input"
          type="text"
          :placeholder="type === QuestionType.Blank ? '请输入填空答案' : '请输入参考答案要点'"
        />
      </view>

      <!-- 解析 -->
      <view class="manual__field">
        <text class="manual__label">解析（选填）</text>
        <textarea
          class="manual__textarea manual__textarea--short"
          v-model="analysis"
          placeholder="请输入答案解析"
          :maxlength="1000"
        />
      </view>

      <!-- 难度 / 分值 -->
      <view class="manual__row">
        <view class="manual__field manual__field--half">
          <text class="manual__label">难度</text>
          <picker :range="DIFFICULTY_LABELS" @change="onDifficultyChange">
            <view class="manual__picker">
              <text class="manual__picker-text">{{ DIFFICULTY_LABELS[difficulty - 1] }}</text>
              <text class="manual__picker-arrow">▾</text>
            </view>
          </picker>
        </view>
        <view class="manual__field manual__field--half">
          <text class="manual__label">分值</text>
          <input v-model="scoreText" class="manual__number-input" type="number" placeholder="1" />
        </view>
      </view>

      <view class="manual__submit" :class="{ 'is-disabled': submitting }" @tap="handleSubmit">
        {{ submitting ? '提交中…' : '保存并继续录入' }}
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 手动导题（P-17）
 * 接口：API-IMP-004 手动录入题目（POST /import/manual，本期占位创建待校对任务）
 * 说明：题型切换联动选项（单选/多选动态增删 2~8 项、判断固定 正确/错误、填空/简答隐藏选项）；
 *       提交成功后重置表单可继续添加下一题。
 */
import { computed, onMounted, ref, watch } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { createQuestionManual } from '@/api/importer'
import { useBankStore } from '@/stores/bank'
import { QuestionType, type QuestionBank, type QuestionOption } from '@/types'

const TYPE_ITEMS: Array<{ value: QuestionType; label: string }> = [
  { value: QuestionType.Single, label: '单选' },
  { value: QuestionType.Multiple, label: '多选' },
  { value: QuestionType.Judge, label: '判断' },
  { value: QuestionType.Blank, label: '填空' },
  { value: QuestionType.Essay, label: '简答' }
]

const DIFFICULTY_LABELS = ['入门', '简单', '中等', '较难', '困难']
const OPTION_KEYS = 'ABCDEFGH'

const bankStore = useBankStore()
const banks = ref<QuestionBank[]>([])
const bankId = ref(0)
const type = ref<QuestionType>(QuestionType.Single)
const title = ref('')
const options = ref<QuestionOption[]>([])
const answer = ref('')
const analysis = ref('')
const difficulty = ref(3)
const scoreText = ref('1')
const submitting = ref(false)

const bankTitles = computed(() => banks.value.map((bank) => bank.title))
const currentBankTitle = computed(
  () => banks.value.find((bank) => bank.id === bankId.value)?.title ?? '请选择题库'
)

onLoad((options) => {
  const fromQuery = options?.bank_id ? Number(options.bank_id) : 0
  if (fromQuery) bankId.value = fromQuery
})

onMounted(async () => {
  if (!bankStore.banks.length) {
    await bankStore.loadMyBanks(true)
  }
  banks.value = bankStore.banks
  if (!bankId.value) {
    const last = bankStore.getLastBankId()
    bankId.value = last ?? banks.value[0]?.id ?? 0
  }
})

// 切换题型时重置选项与答案（immediate：进入页面按默认题型初始化）
watch(type, () => {
  resetOptions()
}, { immediate: true })

/** 按题型生成默认选项 */
function resetOptions() {
  answer.value = ''
  if (type.value === QuestionType.Judge) {
    options.value = [
      { key: 'A', content: '正确' },
      { key: 'B', content: '错误' }
    ]
  } else if (type.value === QuestionType.Single || type.value === QuestionType.Multiple) {
    options.value = ['A', 'B', 'C', 'D'].map((key) => ({ key, content: '' }))
  } else {
    options.value = []
  }
}

function switchType(value: QuestionType) {
  type.value = value
}

function addOption() {
  if (options.value.length >= 8) return
  options.value.push({ key: OPTION_KEYS[options.value.length], content: '' })
}

function removeOption(index: number) {
  if (options.value.length <= 2) return
  options.value.splice(index, 1)
  // 删除后重排选项字母
  options.value.forEach((option, i) => (option.key = OPTION_KEYS[i]))
  // 同步清理答案中已删除的字母
  answer.value = answer.value
    .split('')
    .filter((key) => options.value.some((option) => option.key === key))
    .sort()
    .join('')
}

function toggleMultiAnswer(key: string) {
  const selected = answer.value.split('').filter(Boolean)
  answer.value = selected.includes(key)
    ? selected.filter((item) => item !== key).sort().join('')
    : [...selected, key].sort().join('')
}

function onBankChange(e: { detail: { value: number } }) {
  bankId.value = banks.value[e.detail.value]?.id ?? 0
}

function onDifficultyChange(e: { detail: { value: number } }) {
  difficulty.value = Number(e.detail.value) + 1
}

async function handleSubmit() {
  if (submitting.value) return
  if (!bankId.value) {
    uni.showToast({ title: '请先选择题库', icon: 'none' })
    return
  }
  if (!title.value.trim()) {
    uni.showToast({ title: '请输入题干', icon: 'none' })
    return
  }
  if (!answer.value.trim()) {
    uni.showToast({ title: '请设置正确答案', icon: 'none' })
    return
  }
  if (
    (type.value === QuestionType.Single || type.value === QuestionType.Multiple) &&
    options.value.some((option) => !option.content.trim())
  ) {
    uni.showToast({ title: '请补全选项内容', icon: 'none' })
    return
  }
  // 防重复提交（loading 锁）
  submitting.value = true
  try {
    await createQuestionManual({
      bank_id: bankId.value,
      type: type.value,
      title: title.value.trim(),
      options: options.value.map((option) => ({ key: option.key, content: option.content.trim() })),
      answer: answer.value.trim(),
      analysis: analysis.value.trim() || undefined,
      difficulty: difficulty.value,
      score: Number(scoreText.value) || 1
    })
    uni.showToast({ title: '已提交，可在解析任务中校对', icon: 'none' })
    // 成功后清空表单，保留题型与题库便于继续录入
    title.value = ''
    analysis.value = ''
    answer.value = ''
    if (type.value === QuestionType.Single || type.value === QuestionType.Multiple) {
      options.value.forEach((option) => (option.content = ''))
    }
  } finally {
    submitting.value = false
  }
}
</script>

<style lang="scss" scoped>
.manual {
  min-height: 100vh;
  padding: $spacing-lg;
  background-color: $color-bg-page;

  &__card {
    padding: $spacing-lg;
    background-color: $color-bg-card;
    border-radius: $radius-xl;
  }

  &__field {
    padding: $spacing-md 0;
    border-bottom: 1rpx solid $color-divider;

    &--half {
      flex: 1;
      border-bottom: none;

      &:first-child {
        margin-right: $spacing-md;
      }
    }
  }

  &__row {
    display: flex;
    padding-top: $spacing-sm;
  }

  &__label {
    display: block;
    font-size: $font-size-sm;
    font-weight: 600;
    color: $color-text-primary;
    margin-bottom: $spacing-sm;
  }

  &__picker {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 76rpx;
    padding: 0 $spacing-md;
    background-color: $color-bg-page;
    border-radius: $radius-md;
  }

  &__picker-text {
    font-size: $font-size-sm;
    color: $color-text-primary;
  }

  &__picker-arrow {
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__types {
    display: flex;
    flex-wrap: wrap;
  }

  &__type {
    padding: 10rpx 30rpx;
    margin: 0 $spacing-sm $spacing-sm 0;
    border: 2rpx solid $color-border;
    border-radius: $radius-circle;
    font-size: $font-size-sm;
    color: $color-text-regular;

    &.is-active {
      border-color: $color-primary;
      background-color: $color-primary-bg;
      color: $color-primary;
      font-weight: 600;
    }
  }

  &__textarea {
    width: 100%;
    height: 200rpx;
    padding: $spacing-md;
    box-sizing: border-box;
    background-color: $color-bg-page;
    border-radius: $radius-md;
    font-size: $font-size-base;
    line-height: 1.6;
    color: $color-text-primary;

    &--short {
      height: 160rpx;
    }
  }

  &__option {
    display: flex;
    align-items: center;
    margin-bottom: $spacing-sm;

    &.is-fixed {
      opacity: 0.9;
    }
  }

  &__option-key {
    width: 52rpx;
    height: 52rpx;
    border-radius: $radius-circle;
    border: 2rpx solid $color-border;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: $spacing-sm;
    flex-shrink: 0;
    font-size: $font-size-sm;
    color: $color-text-secondary;
  }

  &__option-input {
    flex: 1;
    height: 76rpx;
    padding: 0 $spacing-md;
    background-color: $color-bg-page;
    border-radius: $radius-md;
    font-size: $font-size-sm;
    color: $color-text-primary;
  }

  &__option-content {
    flex: 1;
    font-size: $font-size-sm;
    color: $color-text-regular;
  }

  &__option-remove {
    padding: 0 $spacing-xs 0 $spacing-md;
    font-size: $font-size-lg;
    color: $color-text-placeholder;
  }

  &__option-add {
    height: 72rpx;
    border: 2rpx dashed $color-border;
    border-radius: $radius-md;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: $font-size-sm;
    color: $color-primary;
  }

  &__answers {
    display: flex;
    flex-wrap: wrap;
  }

  &__answer {
    width: 88rpx;
    height: 88rpx;
    margin: 0 $spacing-md $spacing-sm 0;
    border: 2rpx solid $color-border;
    border-radius: $radius-circle;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: $font-size-base;
    color: $color-text-regular;

    &.is-active {
      border-color: $color-primary;
      background-color: $color-primary;
      color: $color-text-inverse;
      font-weight: 600;
    }
  }

  &__answer-input {
    width: 100%;
    height: 80rpx;
    padding: 0 $spacing-md;
    box-sizing: border-box;
    background-color: $color-bg-page;
    border-radius: $radius-md;
    font-size: $font-size-sm;
    color: $color-text-primary;
  }

  &__number-input {
    width: 100%;
    height: 76rpx;
    padding: 0 $spacing-md;
    box-sizing: border-box;
    background-color: $color-bg-page;
    border-radius: $radius-md;
    font-size: $font-size-sm;
    color: $color-text-primary;
  }

  &__submit {
    margin-top: $spacing-lg;
    height: 88rpx;
    border-radius: 44rpx;
    background: $color-primary-gradient;
    color: $color-text-inverse;
    font-size: $font-size-lg;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;

    &.is-disabled {
      opacity: 0.5;
    }
  }
}
</style>
