<template>
  <view class="login">
    <nav-bar title="" theme="light" :show-back="true" />

    <view class="login__body">
      <text class="login__title">欢迎使用识途刷题</text>
      <text class="login__subtitle">登录后可同步题库、错题与学习进度</text>

      <view class="login__form">
        <view class="login__field">
          <text class="login__prefix">+86</text>
          <input
            v-model="mobile"
            class="login__input"
            type="number"
            maxlength="11"
            placeholder="请输入手机号"
            placeholder-class="login__placeholder"
          />
        </view>

        <view class="login__field">
          <input
            v-model="code"
            class="login__input"
            type="number"
            maxlength="6"
            placeholder="请输入验证码"
            placeholder-class="login__placeholder"
          />
          <text class="login__code-btn" :class="{ 'is-disabled': countdown > 0 }" @tap="handleSendCode">
            {{ countdown > 0 ? `${countdown}s 后重发` : '获取验证码' }}
          </text>
        </view>

        <view class="login__submit" :class="{ 'is-disabled': !canSubmit }" @tap="handleLogin">登录 / 注册</view>

        <view class="login__agree" @tap="agreed = !agreed">
          <view class="login__checkbox" :class="{ 'is-checked': agreed }">
            <text v-if="agreed">✓</text>
          </view>
          <text class="login__agree-text">
            我已阅读并同意《用户协议》与《隐私政策》
          </text>
        </view>
      </view>

      <view class="login__other">
        <view class="login__divider">
          <view class="login__divider-line" />
          <text class="login__divider-text">其他登录方式</text>
          <view class="login__divider-line" />
        </view>
        <view class="login__wechat" @tap="handleWechatLogin">微信一键登录</view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 登录页（P-06）
 * 接口：API-AUTH-001 发送验证码、API-AUTH-002 手机号登录、API-AUTH-003 微信登录
 * 合规：必须勾选用户协议与隐私政策（见 docs/00 §十五 非功能需求）
 */
import { computed, ref } from 'vue'
import { sendSmsCode, loginByWechat } from '@/api/auth'
import { useUserStore } from '@/stores/user'
import { getPlatformLoginCode } from '@/utils/platform'

const userStore = useUserStore()

const mobile = ref('')
const code = ref('')
const agreed = ref(false)
const countdown = ref(0)

const canSubmit = computed(() => mobile.value.length === 11 && code.value.length >= 4 && agreed.value)

async function handleSendCode() {
  if (countdown.value > 0) return
  if (mobile.value.length !== 11) {
    uni.showToast({ title: '请输入正确的手机号', icon: 'none' })
    return
  }
  await sendSmsCode(mobile.value)
  uni.showToast({ title: '验证码已发送', icon: 'none' })
  countdown.value = 60
  const timer = setInterval(() => {
    countdown.value -= 1
    if (countdown.value <= 0) clearInterval(timer)
  }, 1000)
}

async function handleLogin() {
  if (!agreed.value) {
    uni.showToast({ title: '请先阅读并同意用户协议', icon: 'none' })
    return
  }
  if (!canSubmit.value) {
    uni.showToast({ title: '请填写完整的手机号与验证码', icon: 'none' })
    return
  }
  await userStore.login(mobile.value, code.value)
  uni.showToast({ title: '登录成功', icon: 'success' })
  setTimeout(() => uni.navigateBack(), 600)
}

async function handleWechatLogin() {
  const wxCode = await getPlatformLoginCode()
  if (!wxCode) {
    uni.showToast({ title: '当前环境不支持微信登录', icon: 'none' })
    return
  }
  await loginByWechat(wxCode)
  uni.showToast({ title: '登录成功', icon: 'success' })
  setTimeout(() => uni.navigateBack(), 600)
}
</script>

<style lang="scss" scoped>
.login {
  min-height: 100vh;
  background-color: $color-bg-card;

  &__body {
    padding: $spacing-xl $spacing-xl 0;
  }

  &__title {
    display: block;
    font-size: $font-size-xxl;
    font-weight: 700;
    color: $color-text-primary;
  }

  &__subtitle {
    display: block;
    margin-top: $spacing-sm;
    font-size: $font-size-sm;
    color: $color-text-secondary;
  }

  &__form {
    margin-top: $spacing-xl;
  }

  &__field {
    display: flex;
    align-items: center;
    height: 96rpx;
    border-bottom: 1rpx solid $color-divider;
    margin-bottom: $spacing-sm;
  }

  &__prefix {
    font-size: $font-size-base;
    color: $color-text-primary;
    margin-right: $spacing-md;
  }

  &__input {
    flex: 1;
    font-size: $font-size-base;
    color: $color-text-primary;
  }

  &__placeholder {
    color: $color-text-placeholder;
  }

  &__code-btn {
    font-size: $font-size-sm;
    color: $color-primary;

    &.is-disabled {
      color: $color-text-placeholder;
    }
  }

  &__submit {
    margin-top: $spacing-xl;
    height: 88rpx;
    border-radius: 44rpx;
    background: $color-primary-gradient;
    color: $color-text-inverse;
    font-size: $font-size-md;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;

    &.is-disabled {
      opacity: 0.5;
    }
  }

  &__agree {
    display: flex;
    align-items: flex-start;
    margin-top: $spacing-lg;
  }

  &__checkbox {
    width: 32rpx;
    height: 32rpx;
    border-radius: $radius-circle;
    border: 2rpx solid $color-border;
    margin-right: $spacing-xs;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 20rpx;
    color: $color-text-inverse;

    &.is-checked {
      background-color: $color-primary;
      border-color: $color-primary;
    }
  }

  &__agree-text {
    font-size: $font-size-xs;
    color: $color-text-secondary;
    line-height: 1.6;
  }

  &__other {
    margin-top: $spacing-xl * 2;
  }

  &__divider {
    display: flex;
    align-items: center;
  }

  &__divider-line {
    flex: 1;
    height: 1rpx;
    background-color: $color-divider;
  }

  &__divider-text {
    margin: 0 $spacing-md;
    font-size: $font-size-xs;
    color: $color-text-placeholder;
  }

  &__wechat {
    margin-top: $spacing-lg;
    height: 88rpx;
    border-radius: 44rpx;
    border: 2rpx solid $color-success;
    color: $color-success;
    font-size: $font-size-md;
    display: flex;
    align-items: center;
    justify-content: center;
  }
}
</style>
