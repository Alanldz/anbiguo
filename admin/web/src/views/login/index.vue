<template>
  <div class="login-wrapper">
    <el-card class="login-card" shadow="always">
      <div class="login-card__brand">
        <div class="login-card__logo">识途刷题 · 总管理后台</div>
        <div class="login-card__slogan">用识途，备考路上不走弯路</div>
      </div>

      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-position="top"
        @keyup.enter="handleSubmit"
      >
        <el-form-item label="用户名" prop="username">
          <el-input v-model="form.username" placeholder="请输入管理员用户名" :prefix-icon="User" />
        </el-form-item>
        <el-form-item label="密码" prop="password">
          <el-input
            v-model="form.password"
            type="password"
            show-password
            placeholder="请输入密码"
            :prefix-icon="Lock"
          />
        </el-form-item>
        <el-button type="primary" :loading="loading" class="login-card__submit" @click="handleSubmit">
          登 录
        </el-button>
      </el-form>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, type FormInstance, type FormRules } from 'element-plus'
import { User, Lock } from '@element-plus/icons-vue'
import { useAuthStore } from '@/stores/auth'
import { login } from '@/api/auth'

const router = useRouter()
const auth = useAuthStore()
const formRef = ref<FormInstance>()
const loading = ref(false)

const form = reactive({
  username: '',
  password: '',
})

const rules: FormRules = {
  username: [{ required: true, message: '请输入用户名', trigger: 'blur' }],
  password: [{ required: true, message: '请输入密码', trigger: 'blur' }],
}

async function handleSubmit() {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    loading.value = true
    try {
      const data = await login(form.username, form.password)
      auth.setAuth(data.token, data.admin)
      ElMessage.success('登录成功')
      router.replace('/dashboard')
    } catch {
      // 错误已由 request 拦截器统一提示
    } finally {
      loading.value = false
    }
  })
}
</script>

<style scoped lang="scss">
.login-card {
  width: 380px;
  border-radius: 12px;

  &__brand {
    text-align: center;
    margin-bottom: 24px;
  }

  &__logo {
    font-size: 20px;
    font-weight: 700;
    color: #2563eb;
  }

  &__slogan {
    margin-top: 8px;
    font-size: 13px;
    color: #6b7280;
  }

  &__submit {
    width: 100%;
    margin-top: 8px;
  }
}
</style>
