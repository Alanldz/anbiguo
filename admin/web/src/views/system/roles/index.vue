<template>
  <div class="page-container">
    <div class="filter-bar">
      <el-button type="success" :icon="Plus" @click="openCreate">新建角色</el-button>
    </div>

    <el-table v-loading="loading" :data="list" border stripe>
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column prop="name" label="角色名称" />
      <el-table-column prop="code" label="角色标识" />
      <el-table-column prop="description" label="描述" />
      <el-table-column label="权限数" width="90">
        <template #default="{ row }">{{ row.permission_ids?.length || 0 }}</template>
      </el-table-column>
      <el-table-column label="操作" width="160" fixed="right">
        <template #default="{ row }">
          <el-button link type="primary" :icon="Edit" @click="openEdit(row)">编辑</el-button>
          <el-button link type="danger" :icon="Delete" @click="handleDelete(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="520px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
        <el-form-item label="角色名称" prop="name">
          <el-input v-model="form.name" placeholder="如 内容运营" />
        </el-form-item>
        <el-form-item label="角色标识" prop="code">
          <el-input v-model="form.code" :disabled="isEdit" placeholder="如 content.ops" />
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="form.description" type="textarea" :rows="2" />
        </el-form-item>
        <el-form-item label="权限">
          <el-tree
            ref="treeRef"
            :data="permissionTree"
            :props="{ label: 'name', children: 'children' }"
            node-key="id"
            show-checkbox
            default-expand-all
            class="role-tree"
          />
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
import { Plus, Edit, Delete } from '@element-plus/icons-vue'
import { fetchRoleList, createRole, updateRole, deleteRole, fetchPermissionTree } from '@/api/role'
import type { RoleItem, PermissionNode, RolePayload } from '@/types/api.d'

const loading = ref(false)
const submitting = ref(false)
const list = ref<RoleItem[]>([])
const permissionTree = ref<PermissionNode[]>([])

const dialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref<number | null>(null)
const dialogTitle = ref('')
const formRef = ref<FormInstance>()
const treeRef = ref<InstanceType<typeof import('element-plus').ElTree> | null>(null)

const form = reactive<RolePayload>({
  name: '',
  code: '',
  description: '',
  permission_ids: [],
})

const rules: FormRules = {
  name: [{ required: true, message: '请输入角色名称', trigger: 'blur' }],
  code: [{ required: true, message: '请输入角色标识', trigger: 'blur' }],
}

async function loadData() {
  loading.value = true
  try {
    list.value = await fetchRoleList()
  } finally {
    loading.value = false
  }
}

async function loadPermissions() {
  permissionTree.value = await fetchPermissionTree()
}

// 收集所有被勾选节点（含半选父节点）
function collectCheckedKeys(): number[] {
  const tree = treeRef.value as unknown as {
    getCheckedKeys: (leafOnly?: boolean) => number[]
    getHalfCheckedKeys: () => number[]
  }
  if (!tree) return []
  const checked = tree.getCheckedKeys(false)
  const half = tree.getHalfCheckedKeys()
  return [...checked, ...half]
}

function setCheckedKeys(keys: number[]) {
  const tree = treeRef.value as unknown as { setCheckedKeys: (keys: number[]) => void }
  tree?.setCheckedKeys(keys || [])
}

function openCreate() {
  isEdit.value = false
  editId.value = null
  dialogTitle.value = '新建角色'
  Object.assign(form, { name: '', code: '', description: '', permission_ids: [] })
  dialogVisible.value = true
  setCheckedKeys([])
}

function openEdit(row: RoleItem) {
  isEdit.value = true
  editId.value = row.id
  dialogTitle.value = '编辑角色'
  Object.assign(form, {
    name: row.name,
    code: row.code,
    description: row.description || '',
    permission_ids: [...row.permission_ids],
  })
  dialogVisible.value = true
  setCheckedKeys(row.permission_ids)
}

async function handleSubmit() {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    form.permission_ids = collectCheckedKeys()
    submitting.value = true
    try {
      if (isEdit.value && editId.value !== null) {
        await updateRole(editId.value, { ...form })
        ElMessage.success('编辑成功')
      } else {
        await createRole({ ...form })
        ElMessage.success('创建成功')
      }
      dialogVisible.value = false
      loadData()
    } finally {
      submitting.value = false
    }
  })
}

function handleDelete(row: RoleItem) {
  ElMessageBox.confirm(`确定删除角色「${row.name}」吗？`, '提示', { type: 'warning' })
    .then(async () => {
      await deleteRole(row.id)
      ElMessage.success('已删除')
      loadData()
    })
    .catch(() => {})
}

onMounted(() => {
  loadData()
  loadPermissions()
})
</script>

<style scoped lang="scss">
.role-tree {
  width: 100%;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 8px;
  max-height: 320px;
  overflow-y: auto;
}
</style>
