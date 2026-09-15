<template>
  <div class="page-container">
    <div class="filter-bar">
      <el-radio-group v-model="type" @change="loadData">
        <el-radio-button value="bank">银行题库分类</el-radio-button>
        <el-radio-button value="file">资料分类</el-radio-button>
      </el-radio-group>
      <el-button type="success" :icon="Plus" @click="openCreate(null)">新建分类</el-button>
    </div>

    <el-table
      v-loading="loading"
      :data="treeData"
      row-key="id"
      border
      stripe
      default-expand-all
      :tree-props="{ children: 'children' }"
    >
      <el-table-column prop="name" label="名称" min-width="220">
        <template #default="{ row }">
          <span>{{ row.icon ? row.icon + ' ' : '' }}{{ row.name }}</span>
        </template>
      </el-table-column>
      <el-table-column prop="code" label="编码" width="150" />
      <el-table-column prop="level" label="层级" width="80" />
      <el-table-column prop="count" label="数量" width="90" />
      <el-table-column prop="sort_order" label="排序" width="80" />
      <el-table-column label="状态" width="90">
        <template #default="{ row }">
          <el-tag :type="CATEGORY_STATUS_MAP[row.status]?.type || 'info'">
            {{ CATEGORY_STATUS_MAP[row.status]?.label || '未知' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="创建时间" width="180" />
      <el-table-column label="操作" width="200" fixed="right">
        <template #default="{ row }">
          <el-button link type="primary" :icon="Plus" @click="openCreate(row)">子级</el-button>
          <el-button link type="primary" :icon="Edit" @click="openEdit(row)">编辑</el-button>
          <el-button link type="danger" :icon="Delete" @click="handleDelete(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="520px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
        <el-form-item v-if="!isEdit" label="上级分类">
          <el-select v-model="form.parent_id" style="width: 100%" clearable placeholder="顶级分类">
            <el-option
              v-for="opt in parentOptions"
              :key="opt.id"
              :label="opt.name"
              :value="opt.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="名称" prop="name">
          <el-input v-model="form.name" placeholder="分类名称" />
        </el-form-item>
        <el-form-item v-if="!isEdit" label="编码" prop="code">
          <el-input v-model="form.code" placeholder="唯一编码（新建后不可编辑）" />
        </el-form-item>
        <el-form-item label="图标">
          <el-input v-model="form.icon" placeholder="图标 / emoji" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sort_order" :min="0" />
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="form.status">
            <el-radio :value="CATEGORY_STATUS_NORMAL">正常</el-radio>
            <el-radio :value="CATEGORY_STATUS_HIDDEN">隐藏</el-radio>
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
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { Plus, Edit, Delete } from '@element-plus/icons-vue'
import { fetchCategories, createCategory, updateCategory, deleteCategory } from '@/api/category'
import {
  CATEGORY_STATUS_MAP,
  CATEGORY_STATUS_NORMAL,
  CATEGORY_STATUS_HIDDEN,
} from '@/constants'
import type { CategoryItem, CategoryTreeItem } from '@/types/api.d'

const loading = ref(false)
const submitting = ref(false)
const type = ref<'bank' | 'file'>('bank')
const flatList = ref<CategoryItem[]>([])
const treeData = ref<CategoryTreeItem[]>([])

/** 由平铺列表按 parent_id 组装树 */
function buildTree(flat: CategoryItem[]): CategoryTreeItem[] {
  const map = new Map<number, CategoryTreeItem>()
  flat.forEach((it) => map.set(it.id, { ...it, children: [] }))
  const roots: CategoryTreeItem[] = []
  flat.forEach((it) => {
    const node = map.get(it.id)!
    const parent = it.parent_id ? map.get(it.parent_id) : undefined
    if (parent) parent.children.push(node)
    else roots.push(node)
  })
  return roots
}

/** 收集某节点及其所有后代 id（编辑时禁止选作上级） */
function collectDescendants(flat: CategoryItem[], rootId: number): Set<number> {
  const childrenOf = new Map<number, number[]>()
  flat.forEach((it) => {
    if (it.parent_id) {
      const arr = childrenOf.get(it.parent_id) || []
      arr.push(it.id)
      childrenOf.set(it.parent_id, arr)
    }
  })
  const result = new Set<number>([rootId])
  const stack = [rootId]
  while (stack.length) {
    const cur = stack.pop()!
    ;(childrenOf.get(cur) || []).forEach((cid) => {
      result.add(cid)
      stack.push(cid)
    })
  }
  return result
}

const dialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref<number | null>(null)
const dialogTitle = ref('')
const formRef = ref<FormInstance>()

interface CategoryForm {
  parent_id: number
  name: string
  code: string
  icon: string
  sort_order: number
  status: number
}
const emptyForm = (): CategoryForm => ({
  parent_id: 0,
  name: '',
  code: '',
  icon: '',
  sort_order: 0,
  status: CATEGORY_STATUS_NORMAL,
})
const form = reactive(emptyForm())

const rules: FormRules = {
  name: [{ required: true, message: '请输入分类名称', trigger: 'blur' }],
  code: [{ required: true, message: '请输入分类编码', trigger: 'blur' }],
}

/** 可选上级（编辑时排除自身及后代） */
const parentOptions = computed<CategoryItem[]>(() => {
  if (!isEdit.value || editId.value === null) return flatList.value
  const exclude = collectDescendants(flatList.value, editId.value)
  return flatList.value.filter((it) => !exclude.has(it.id))
})

async function loadData() {
  loading.value = true
  try {
    const list = (await fetchCategories(type.value)).list
    flatList.value = list
    treeData.value = buildTree(list)
  } finally {
    loading.value = false
  }
}

function resetForm() {
  Object.assign(form, emptyForm())
}

function openCreate(parent: CategoryItem | null) {
  isEdit.value = false
  editId.value = null
  dialogTitle.value = '新建分类'
  resetForm()
  form.parent_id = parent ? parent.id : 0
  dialogVisible.value = true
}

function openEdit(row: CategoryTreeItem) {
  isEdit.value = true
  editId.value = row.id
  dialogTitle.value = '编辑分类'
  Object.assign(form, {
    parent_id: row.parent_id,
    name: row.name,
    code: row.code,
    icon: row.icon,
    sort_order: row.sort_order,
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
        await updateCategory(editId.value, {
          name: form.name,
          icon: form.icon,
          sort_order: form.sort_order,
          status: form.status,
        })
        ElMessage.success('编辑成功')
      } else {
        await createCategory({
          type: type.value,
          parent_id: form.parent_id,
          name: form.name,
          code: form.code,
          icon: form.icon,
          sort_order: form.sort_order,
          status: form.status,
        })
        ElMessage.success('创建成功')
      }
      dialogVisible.value = false
      loadData()
    } finally {
      submitting.value = false
    }
  })
}

function handleDelete(row: CategoryTreeItem) {
  ElMessageBox.confirm(`确定删除分类「${row.name}」吗？若有子级或正在使用将被后端拒绝。`, '提示', {
    type: 'warning',
  })
    .then(async () => {
      try {
        await deleteCategory(row.id)
        ElMessage.success('已删除')
        loadData()
      } catch {
        // 错误已由拦截器按后端 message 提示
      }
    })
    .catch(() => {})
}

onMounted(loadData)
</script>

<style scoped lang="scss">
.pager {
  margin-top: 16px;
  justify-content: flex-end;
}
</style>
