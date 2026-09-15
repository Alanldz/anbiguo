<template>
  <div class="page-container">
    <div class="filter-bar">
      <el-input v-model="query.keyword" placeholder="用户名/昵称" clearable @keyup.enter="loadData" />
      <el-button type="primary" :icon="Search" @click="loadData">查询</el-button>
      <el-button :icon="Refresh" @click="resetQuery">重置</el-button>
      <el-button type="success" :icon="Plus" @click="openCreate">新建管理员</el-button>
    </div>

    <el-table v-loading="loading" :data="list" border stripe>
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column prop="username" label="用户名" />
      <el-table-column prop="nickname" label="昵称" />
      <el-table-column label="角色">
        <template #default="{ row }">
          <el-tag v-for="r in row.roles" :key="r.id" class="role-tag">{{ r.name }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="状态" width="100">
        <template #default="{ row }">
          <el-tag :type="ADMIN_STATUS_MAP[row.status]?.type || 'info'">
            {{ ADMIN_STATUS_MAP[row.status]?.label || '未知' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="last_login_at" label="最近登录" width="180" />
      <el-table-column label="操作" width="160" fixed="right">
        <template #default="{ row }">
          <el-button link type="primary" :icon="Edit" @click="openEdit(row)">编辑</el-button>
          <el-button link type="danger" :icon="Delete" @click="handleDelete(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-pagination
      v-model:current-page="query.page"
      v-model:page-size="query.page_size"
      :total="total"
      :page-sizes="[10, 20, 50]"
      layout="total, sizes, prev, pager, next"
      class="pager"
      @current-change="loadData"
      @size-change="loadData"
    />

    <!-- 新建 / 编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="480px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
        <el-form-item label="用户名" prop="username">
          <el-input v-model="form.username" :disabled="isEdit" placeholder="登录用户名" />
        </el-form-item>
        <el-form-item label="昵称" prop="nickname">
          <el-input v-model="form.nickname" placeholder="显示昵称" />
        </el-form-item>
        <el-form-item :label="isEdit ? '重置密码' : '密码'" prop="password">
          <el-input
            v-model="form.password"
            type="password"
            show-password
            :placeholder="isEdit ? '留空则不修改' : '登录密码'"
          />
        </el-form-item>
        <el-form-item label="角色" prop="role_ids">
          <el-select v-model="form.role_ids" multiple placeholder="选择角色" style="width: 100%">
            <el-option v-for="r in roleOptions" :key="r.id" :label="r.name" :value="r.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="form.status">
            <el-radio :value="1">启用</el-radio>
            <el-radio :value="2">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { Search, Refresh, Plus, Edit, Delete } from '@element-plus/icons-vue'
import {
  fetchAdminList,
  createAdmin,
  updateAdmin,
  deleteAdmin,
} from '@/api/admin'
import { fetchRoleList } from '@/api/role'
import { ADMIN_STATUS_MAP, DEFAULT_PAGE_SIZE } from '@/constants'
import type { AdminItem, RoleItem, AdminCreatePayload } from '@/types/api.d'

const loading = ref(false)
const submitting = ref(false)
const list = ref<AdminItem[]>([])
const total = ref(0)
const roleOptions = ref<RoleItem[]>([])

const query = reactive({
  keyword: '',
  page: 1,
  page_size: DEFAULT_PAGE_SIZE,
})

const dialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref<number | null>(null)
const dialogTitle = ref('')
const formRef = ref<FormInstance>()

const form = reactive<AdminCreatePayload & { id?: number }>({
  username: '',
  nickname: '',
  password: '',
  role_ids: [],
  status: 1,
})

const rules: FormRules = {
  username: [{ required: true, message: '请输入用户名', trigger: 'blur' }],
  nickname: [{ required: true, message: '请输入昵称', trigger: 'blur' }],
  password: [{ required: !isEdit.value, message: '请输入密码', trigger: 'blur' }],
  role_ids: [{ required: true, type: 'array', message: '请选择角色', trigger: 'change' }],
}

async function loadData() {
  loading.value = true
  try {
    const res = await fetchAdminList({ ...query })
    list.value = res.list
    total.value = res.pagination.total
  } finally {
    loading.value = false
  }
}

function resetQuery() {
  query.keyword = ''
  query.page = 1
  loadData()
}

async function loadRoles() {
  roleOptions.value = await fetchRoleList()
}

function openCreate() {
  isEdit.value = false
  editId.value = null
  dialogTitle.value = '新建管理员'
  Object.assign(form, { username: '', nickname: '', password: '', role_ids: [], status: 1 })
  dialogVisible.value = true
}

function openEdit(row: AdminItem) {
  isEdit.value = true
  editId.value = row.id
  dialogTitle.value = '编辑管理员'
  Object.assign(form, {
    username: row.username,
    nickname: row.nickname,
    password: '',
    role_ids: row.roles.map((r) => r.id),
    status: row.status,
  })
  dialogVisible.value = true
}

async function handleSubmit() {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    submitting.value = true
    try {
      if (isEdit.value && editId.value !== null) {
        const { username: _u, ...rest } = form
        await updateAdmin(editId.value, rest)
        ElMessage.success('编辑成功')
      } else {
        await createAdmin({ ...form })
        ElMessage.success('创建成功')
      }
      dialogVisible.value = false
      loadData()
    } finally {
      submitting.value = false
    }
  })
}

function handleDelete(row: AdminItem) {
  ElMessageBox.confirm(`确定删除管理员「${row.nickname}」吗？`, '提示', {
    type: 'warning',
  })
    .then(async () => {
      await deleteAdmin(row.id)
      ElMessage.success('已删除')
      loadData()
    })
    .catch(() => {})
}

onMounted(() => {
  loadData()
  loadRoles()
})
</script>

<style scoped lang="scss">
.pager {
  margin-top: 16px;
  justify-content: flex-end;
}
.role-tag {
  margin-right: 4px;
}
</style>
