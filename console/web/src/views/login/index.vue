<template>
  <div class="login-wrapper">
    <!-- 左侧品牌区 -->
    <div class="login-banner">
      <div class="login-banner__inner">
        <div class="login-banner__logo">识途刷题</div>
        <div class="login-banner__sub">用户电脑端后台</div>
        <div class="login-banner__desc">管理你的题库、题目、资料、错题与考试记录</div>
        <ul class="login-banner__points">
          <li>我的题库 · 题目轻松录入</li>
          <li>错题本 · 精准查漏补缺</li>
          <li>考试记录 · 进步一目了然</li>
        </ul>
      </div>
    </div>

    <!-- 右侧表单卡片 -->
    <div class="login-form-area">
      <el-card class="login-card" shadow="always">
        <el-tabs v-model="activeTab" class="login-card__tabs">
          <el-tab-pane label="密码登录" name="password" />
          <el-tab-pane label="验证码登录" name="code" />
        </el-tabs>

        <!-- 密码登录 -->
        <el-form
          v-if="activeTab === 'password'"
          ref="pwdFormRef"
          :model="pwdForm"
          :rules="pwdRules"
          label-position="top"
          @keyup.enter="handlePasswordLogin"
        >
          <el-form-item label="手机号" prop="mobile">
            <el-input v-model="pwdForm.mobile" placeholder="请输入手机号" :prefix-icon="Iphone" />
          </el-form-item>
          <el-form-item label="密码" prop="password">
            <el-input
              v-model="pwdForm.password"
              type="password"
              show-password
              placeholder="请输入密码"
              :prefix-icon="Lock"
            />
          </el-form-item>
          <el-button type="primary" :loading="loading" class="login-card__submit" @click="handlePasswordLogin">
            登 录
          </el-button>
        </el-form>

        <!-- 验证码登录 -->
        <el-form
          v-else
          ref="codeFormRef"
          :model="codeForm"
          :rules="codeRules"
          label-position="top"
          @keyup.enter="handleCodeLogin"
        >
          <el-form-item label="手机号" prop="mobile">
            <el-input v-model="codeForm.mobile" placeholder="请输入手机号" :prefix-icon="Iphone" />
          </el-form-item>
          <el-form-item label="验证码" prop="code">
            <div class="login-card__code">
              <el-input v-model="codeForm.code" placeholder="请输入验证码" :prefix-icon="Message" />
              <el-button :disabled="countdown > 0" @click="sendCode">
                {{ countdown > 0 ? `${countdown}s 后重发` : '获取验证码' }}
              </el-button>
            </div>
          </el-form-item>
          <el-button type="primary" :loading="loading" class="login-card__submit" @click="handleCodeLogin">
            登 录
          </el-button>
        </el-form>
      </el-card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, type FormInstance, type FormRules } from 'element-plus'
import { Iphone, Lock, Message } from '@element-plus/icons-vue'
import { useAuthStore } from '@/stores/auth'
import { login, sendSmsCode } from '@/api/auth'

const router = useRouter()
const auth = useAuthStore()
const loading = ref(false)
const activeTab = ref<'password' | 'code'>('password')

// 倒计时
const countdown = ref(0)
let timer: ReturnType<typeof setInterval> | undefined

const pwdFormRef = ref<FormInstance>()
const pwdForm = reactive({ mobile: '', password: '' })
const pwdRules: FormRules = {
  mobile: [
    { required: true, message: '请输入手机号', trigger: 'blur' },
    { pattern: /^1\d{10}$/, message: '手机号格式不正确', trigger: 'blur' },
  ],
  password: [{ required: true, message: '请输入密码', trigger: 'blur' }],
}

const codeFormRef = ref<FormInstance>()
const codeForm = reactive({ mobile: '', code: '' })
const codeRules: FormRules = {
  mobile: [
    { required: true, message: '请输入手机号', trigger: 'blur' },
    { pattern: /^1\d{10}$/, message: '手机号格式不正确', trigger: 'blur' },
  ],
  code: [{ required: true, message: '请输入验证码', trigger: 'blur' }],
}

async function handlePasswordLogin() {
  if (!pwdFormRef.value) return
  await pwdFormRef.value.validate(async (valid) => {
    if (!valid) return
    loading.value = true
    try {
      const data = await login({ mobile: pwdForm.mobile, password: pwdForm.password, login_type: 1 })
      auth.setAuth(data.token, data.user)
      ElMessage.success('登录成功')
      router.replace('/dashboard')
    } finally {
      loading.value = false
    }
  })
}

async function handleCodeLogin() {
  if (!codeFormRef.value) return
  await codeFormRef.value.validate(async (valid) => {
    if (!valid) return
    loading.value = true
    try {
      const data = await login({ mobile: codeForm.mobile, code: codeForm.code, login_type: 2 })
      auth.setAuth(data.token, data.user)
      ElMessage.success('登录成功')
      router.replace('/dashboard')
    } finally {
      loading.value = false
    }
  })
}

async function sendCode() {
  if (!/^1\d{10}$/.test(codeForm.mobile)) {
    ElMessage.warning('请先填写正确的手机号')
    return
  }
  try {
    const res = await sendSmsCode({ mobile: codeForm.mobile, scene: 'login' })
    ElMessage.success('验证码已发送')
    if (res.debug_code) {
      ElMessage.info(`本地调试验证码：${res.debug_code}`)
    }
    countdown.value = 60
    timer = setInterval(() => {
      countdown.value -= 1
      if (countdown.value <= 0 && timer) {
        clearInterval(timer)
        timer = undefined
      }
    }, 1000)
  } catch {
    // 错误已统一提示
  }
}

onUnmounted(() => {
  if (timer) clearInterval(timer)
})
</script>

<style scoped lang="scss">
.login-wrapper {
  display: flex;
}

.login-banner {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
  color: #fff;

  &__inner {
    max-width: 360px;
  }

  &__logo {
    font-size: 36px;
    font-weight: 800;
  }

  &__sub {
    margin-top: 8px;
    font-size: 18px;
    opacity: 0.9;
  }

  &__desc {
    margin-top: 24px;
    font-size: 14px;
    opacity: 0.85;
    line-height: 1.8;
  }

  &__points {
    margin-top: 24px;
    padding-left: 20px;
    font-size: 14px;
    line-height: 2;
    opacity: 0.9;
  }
}

.login-form-area {
  width: 460px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f7fa;
}

.login-card {
  width: 380px;
  border-radius: 12px;

  &__tabs {
    margin-bottom: 8px;
  }

  &__submit {
    width: 100%;
    margin-top: 8px;
  }

  &__code {
    display: flex;
    gap: 8px;

    .el-input {
      flex: 1;
    }
  }
}

@media (max-width: 768px) {
  .login-banner {
    display: none;
  }
  .login-form-area {
    width: 100%;
  }
}
</style>
