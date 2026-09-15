<template>
  <view class="feedback">
    <view class="feedback__card">
      <!-- 反馈类型 -->
      <view class="feedback__field">
        <text class="feedback__label">反馈类型</text>
        <view class="feedback__types">
          <view
            v-for="item in TYPE_ITEMS"
            :key="item.value"
            class="feedback__type"
            :class="{ 'is-active': type === item.value }"
            @tap="type = item.value"
          >
            <text>{{ item.label }}</text>
          </view>
        </view>
      </view>

      <!-- 反馈内容 -->
      <view class="feedback__field">
        <text class="feedback__label">反馈内容</text>
        <textarea
          class="feedback__textarea"
          v-model="content"
          placeholder="请描述您遇到的问题或建议（5~500 字）"
          :maxlength="500"
        />
        <text class="feedback__counter" :class="{ 'is-over': contentLength > 500 || (contentLength > 0 && contentLength < 5) }">
          {{ contentLength }}/500
        </text>
      </view>

      <!-- 联系方式 -->
      <view class="feedback__field">
        <text class="feedback__label">联系方式（选填）</text>
        <input
          v-model="contact"
          class="feedback__contact-input"
          type="text"
          :maxlength="64"
          placeholder="手机号 / 微信号，方便我们联系您"
        />
      </view>

      <view class="feedback__submit" :class="{ 'is-disabled': submitting }" @tap="handleSubmit">
        {{ submitting ? '提交中…' : '提交反馈' }}
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 意见反馈提交页（API-FBK-001）
 * 接口：API-FBK-001 提交意见反馈（POST /api/v1/feedbacks，需登录）
 * 说明：类型胶囊单选 + 内容 5~500 字 + 联系方式选填；
 *       提交带 loading 锁防重复，成功后 toast 并延迟返回上一页。
 */
import { computed, ref } from 'vue'
import { submitFeedback } from '@/api/feedback'

const TYPE_ITEMS: Array<{ value: 1 | 2 | 3; label: string }> = [
  { value: 1, label: '功能异常' },
  { value: 2, label: '体验建议' },
  { value: 3, label: '其他' }
]

const type = ref<1 | 2 | 3>(1)
const content = ref('')
const contact = ref('')
const submitting = ref(false)

const contentLength = computed(() => content.value.trim().length)

async function handleSubmit() {
  if (submitting.value) return
  const trimmed = content.value.trim()
  if (trimmed.length < 5 || trimmed.length > 500) {
    uni.showToast({ title: '反馈内容需为 5~500 字', icon: 'none' })
    return
  }
  // 防重复提交（loading 锁）
  submitting.value = true
  try {
    await submitFeedback({
      type: type.value,
      content: trimmed,
      contact: contact.value.trim() || undefined
    })
    uni.showToast({ title: '感谢反馈，我们会尽快处理', icon: 'none' })
    // 延迟返回上一页，让用户看到成功提示
    setTimeout(() => {
      uni.navigateBack()
    }, 1500)
  } finally {
    submitting.value = false
  }
}
</script>

<style lang="scss" scoped>
.feedback {
  min-height: 100vh;
  padding: $spacing-lg;
  background-color: $color-bg-page;

  &__card {
    padding: $spacing-lg;
    background-color: $color-bg-card;
    border-radius: $radius-xl;
  }

  &__field {
    position: relative;
    padding: $spacing-md 0;
    border-bottom: 1rpx solid $color-divider;
  }

  &__label {
    display: block;
    font-size: $font-size-sm;
    font-weight: 600;
    color: $color-text-primary;
    margin-bottom: $spacing-sm;
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
    height: 240rpx;
    padding: $spacing-md;
    box-sizing: border-box;
    background-color: $color-bg-page;
    border-radius: $radius-md;
    font-size: $font-size-base;
    line-height: 1.6;
    color: $color-text-primary;
  }

  &__counter {
    display: block;
    margin-top: $spacing-xs;
    text-align: right;
    font-size: $font-size-xs;
    color: $color-text-placeholder;

    &.is-over {
      color: $color-danger;
    }
  }

  &__contact-input {
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
