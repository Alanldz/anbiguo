<template>
  <div class="page-container">
    <el-row :gutter="16">
      <!-- 左侧账号概要 -->
      <el-col :span="7">
        <el-card shadow="hover" class="account-summary">
          <div class="account-summary__avatar">
            <el-avatar v-if="profile?.avatar" :src="profile.avatar" :size="72" />
            <el-avatar v-else :size="72">{{ avatarText }}</el-avatar>
          </div>
          <div class="account-summary__name">{{ profile?.nickname || '未设置昵称' }}</div>
          <div class="account-summary__uid">UID：{{ profile?.uid || '—' }}</div>
          <el-divider />
          <div class="account-summary__row">
            <span class="account-summary__key">手机号</span>
            <span class="account-summary__val">{{ profile?.mobile || '—' }}</span>
          </div>
          <div class="account-summary__row">
            <span class="account-summary__key">学习天数</span>
            <span class="account-summary__val">{{ profile?.study_days ?? 0 }} 天</span>
          </div>
          <div class="account-summary__row">
            <span class="account-summary__key">累计时长</span>
            <span class="account-summary__val">{{ formatDuration(profile?.study_seconds ?? 0) }}</span>
          </div>
          <div class="account-summary__row">
            <span class="account-summary__key">注册时间</span>
            <span class="account-summary__val">{{ profile?.created_at || '—' }}</span>
          </div>
        </el-card>
      </el-col>

      <!-- 右侧表单区 -->
      <el-col :span="17">
        <el-card shadow="never">
          <el-tabs v-model="activeTab">
            <!-- 基本资料 -->
            <el-tab-pane label="基本资料" name="profile">
              <el-form
                ref="profileFormRef"
                :model="profileForm"
                :rules="profileRules"
                label-width="90px"
                class="account-form"
                v-loading="profileLoading"
              >
                <el-form-item label="昵称" prop="nickname">
                  <el-input v-model="profileForm.nickname" placeholder="请输入昵称" maxlength="20" show-word-limit />
                </el-form-item>
                <el-form-item label="性别" prop="gender">
                  <el-radio-group v-model="profileForm.gender">
                    <el-radio :value="1">男</el-radio>
                    <el-radio :value="2">女</el-radio>
                    <el-radio :value="0">保密</el-radio>
                  </el-radio-group>
                </el-form-item>
                <el-form-item label="生日" prop="birthday">
                  <el-date-picker
                    v-model="profileForm.birthday"
                    type="date"
                    placeholder="选择生日"
                    value-format="YYYY-MM-DD"
                    style="width: 220px"
                  />
                </el-form-item>
                <el-form-item label="所在地区">
                  <div class="account-form__area">
                    <el-input v-model="profileForm.province" placeholder="省份，如：广东省" />
                    <el-input v-model="profileForm.city" placeholder="城市，如：深圳市" />
                  </div>
                </el-form-item>
                <el-form-item label="备考目标">
                  <el-input v-model="profileForm.exam_target" placeholder="如：一级建造师" />
                </el-form-item>
                <el-form-item label="个人简介">
                  <el-input
                    v-model="profileForm.bio"
                    type="textarea"
                    :rows="3"
                    maxlength="200"
                    show-word-limit
                    placeholder="介绍一下自己（选填）"
                  />
                </el-form-item>
                <el-form-item>
                  <el-button type="primary" :loading="profileSubmitting" @click="handleProfileSubmit">
                    保存资料
                  </el-button>
                  <el-button @click="resetProfileForm">重置</el-button>
                </el-form-item>
              </el-form>
            </el-tab-pane>

            <!-- 修改密码 -->
            <el-tab-pane label="修改密码" name="password">
              <el-form
                ref="pwdFormRef"
                :model="pwdForm"
                :rules="pwdRules"
                label-width="90px"
                class="account-form"
              >
                <el-form-item label="原密码" prop="old_password">
                  <el-input
                    v-model="pwdForm.old_password"
                    type="password"
                    show-password
                    placeholder="未设置密码可留空"
                  />
                </el-form-item>
                <el-form-item label="新密码" prop="new_password">
                  <el-input v-model="pwdForm.new_password" type="password" show-password placeholder="6~20 位" />
                </el-form-item>
                <el-form-item label="确认密码" prop="confirm_password">
                  <el-input
                    v-model="pwdForm.confirm_password"
                    type="password"
                    show-password
                    placeholder="请再次输入新密码"
                  />
                </el-form-item>
                <el-form-item>
                  <el-button type="primary" :loading="pwdSubmitting" @click="handlePasswordSubmit">
                    修改密码
                  </el-button>
                </el-form-item>
              </el-form>
            </el-tab-pane>

            <!-- 换绑手机 -->
            <el-tab-pane label="换绑手机" name="mobile">
              <el-form
                ref="mobileFormRef"
                :model="mobileForm"
                :rules="mobileRules"
                label-width="90px"
                class="account-form"
              >
                <el-form-item label="当前手机号">
                  <el-input :model-value="profile?.mobile" disabled />
                </el-form-item>
                <el-form-item label="新手机号" prop="new_mobile">
                  <el-input v-model="mobileForm.new_mobile" placeholder="请输入新手机号" :prefix-icon="Iphone" />
                </el-form-item>
                <el-form-item label="验证码" prop="code">
                  <div class="account-form__code">
                    <el-input v-model="mobileForm.code" placeholder="请输入验证码" :prefix-icon="Message" />
                    <el-button :disabled="countdown > 0" @click="sendCode">
                      {{ countdown > 0 ? `${countdown}s 后重发` : '获取验证码' }}
                    </el-button>
                  </div>
                </el-form-item>
                <el-form-item>
                  <el-button type="primary" :loading="mobileSubmitting" @click="handleMobileSubmit">
                    确认换绑
                  </el-button>
                </el-form-item>
              </el-form>
            </el-tab-pane>
          </el-tabs>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import {
  ElMessage,
  ElMessageBox,
  type FormInstance,
  type FormRules,
} from 'element-plus'
import { Iphone, Message } from '@element-plus/icons-vue'
import { fetchProfile, updateProfile, updatePassword, updateMobile } from '@/api/account'
import { sendSmsCode } from '@/api/auth'
import { useAuthStore } from '@/stores/auth'
import type { ProfileInfo, ProfileUpdatePayload } from '@/types/api.d'

const auth = useAuthStore()
const activeTab = ref<'profile' | 'password' | 'mobile'>('profile')

const profileLoading = ref(false)
const profile = ref<ProfileInfo | null>(null)

const avatarText = computed(() => {
  const name = profile.value?.nickname || profile.value?.mobile || 'U'
  return name.slice(0, 1).toUpperCase()
})

function formatDuration(seconds: number): string {
  const h = Math.floor(seconds / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  if (h > 0) return m > 0 ? `${h}小时${m}分` : `${h}小时`
  if (m > 0) return `${m}分`
  return `${seconds}秒`
}

// ---- 基本资料 ----
const profileFormRef = ref<FormInstance>()
const profileSubmitting = ref(false)
const profileForm = reactive<Required<Pick<ProfileUpdatePayload, 'nickname' | 'gender' | 'birthday' | 'province' | 'city' | 'exam_target' | 'bio'>>>({
  nickname: '',
  gender: 0,
  birthday: '',
  province: '',
  city: '',
  exam_target: '',
  bio: '',
})

const profileRules: FormRules = {
  nickname: [
    { required: true, message: '请输入昵称', trigger: 'blur' },
    { min: 2, max: 20, message: '昵称长度 2~20 个字符', trigger: 'blur' },
  ],
}

function fillProfileForm(data: ProfileInfo) {
  profileForm.nickname = data.nickname || ''
  profileForm.gender = data.gender ?? 0
  profileForm.birthday = data.birthday || ''
  profileForm.province = data.province || ''
  profileForm.city = data.city || ''
  profileForm.exam_target = data.exam_target || ''
  profileForm.bio = data.bio || ''
}

async function loadProfile() {
  profileLoading.value = true
  try {
    const data = await fetchProfile()
    profile.value = data
    fillProfileForm(data)
  } finally {
    profileLoading.value = false
  }
}

function resetProfileForm() {
  if (profile.value) fillProfileForm(profile.value)
}

async function handleProfileSubmit() {
  if (!profileFormRef.value) return
  await profileFormRef.value.validate(async (valid) => {
    if (!valid) return
    profileSubmitting.value = true
    try {
      await updateProfile({ ...profileForm })
      ElMessage.success('资料已更新')
      await loadProfile()
      // 同步顶栏展示的昵称
      const current = auth.user
      if (current && profile.value) current.nickname = profile.value.nickname
    } finally {
      profileSubmitting.value = false
    }
  })
}

// ---- 修改密码 ----
const pwdFormRef = ref<FormInstance>()
const pwdSubmitting = ref(false)
const pwdForm = reactive({
  old_password: '',
  new_password: '',
  confirm_password: '',
})

const pwdRules: FormRules = {
  new_password: [
    { required: true, message: '请输入新密码', trigger: 'blur' },
    { min: 6, max: 20, message: '密码长度 6~20 位', trigger: 'blur' },
  ],
  confirm_password: [
    { required: true, message: '请再次输入新密码', trigger: 'blur' },
    {
      validator: (_rule, value: string, callback: (error?: Error) => void) => {
        if (value !== pwdForm.new_password) {
          callback(new Error('两次输入的密码不一致'))
        } else {
          callback()
        }
      },
      trigger: 'blur',
    },
  ],
}

async function handlePasswordSubmit() {
  if (!pwdFormRef.value) return
  await pwdFormRef.value.validate(async (valid) => {
    if (!valid) return
    pwdSubmitting.value = true
    try {
      await updatePassword({
        old_password: pwdForm.old_password || undefined,
        new_password: pwdForm.new_password,
      })
      ElMessage.success('密码修改成功')
      pwdForm.old_password = ''
      pwdForm.new_password = ''
      pwdForm.confirm_password = ''
      pwdFormRef.value?.clearValidate()
    } finally {
      pwdSubmitting.value = false
    }
  })
}

// ---- 换绑手机 ----
const mobileFormRef = ref<FormInstance>()
const mobileSubmitting = ref(false)
const mobileForm = reactive({ new_mobile: '', code: '' })
const countdown = ref(0)
let timer: ReturnType<typeof setInterval> | undefined

const mobileRules: FormRules = {
  new_mobile: [
    { required: true, message: '请输入新手机号', trigger: 'blur' },
    { pattern: /^1\d{10}$/, message: '手机号格式不正确', trigger: 'blur' },
  ],
  code: [{ required: true, message: '请输入验证码', trigger: 'blur' }],
}

async function sendCode() {
  if (!/^1\d{10}$/.test(mobileForm.new_mobile)) {
    ElMessage.warning('请先填写正确的新手机号')
    return
  }
  try {
    const res = await sendSmsCode({ mobile: mobileForm.new_mobile, scene: 'bind' })
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
    // 错误已由 request 拦截器统一提示
  }
}

async function handleMobileSubmit() {
  if (!mobileFormRef.value) return
  await mobileFormRef.value.validate(async (valid) => {
    if (!valid) return
    ElMessageBox.confirm(`确定将手机号更换为 ${mobileForm.new_mobile} 吗？`, '提示', { type: 'warning' })
      .then(async () => {
        mobileSubmitting.value = true
        try {
          await updateMobile({ new_mobile: mobileForm.new_mobile, code: mobileForm.code })
          ElMessage.success('换绑成功')
          mobileForm.new_mobile = ''
          mobileForm.code = ''
          mobileFormRef.value?.clearValidate()
          await loadProfile()
          // 同步顶栏展示的手机号
          const current = auth.user
          if (current && profile.value) current.mobile = profile.value.mobile
        } finally {
          mobileSubmitting.value = false
        }
      })
      .catch(() => {})
  })
}

onMounted(loadProfile)

onUnmounted(() => {
  if (timer) clearInterval(timer)
})
</script>

<style scoped lang="scss">
.account-summary {
  text-align: center;

  &__avatar {
    display: flex;
    justify-content: center;
  }

  &__name {
    margin-top: 12px;
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
  }

  &__uid {
    margin-top: 4px;
    font-size: 12px;
    color: #9ca3af;
  }

  &__row {
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
    font-size: 13px;
  }

  &__key {
    color: #6b7280;
  }

  &__val {
    color: #1f2937;
    font-weight: 500;
  }
}

.account-form {
  max-width: 520px;

  &__area {
    display: flex;
    gap: 12px;
    width: 100%;
  }

  &__code {
    display: flex;
    gap: 8px;
    width: 100%;

    .el-input {
      flex: 1;
    }
  }
}
</style>
