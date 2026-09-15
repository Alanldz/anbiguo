<template>
  <div class="page-container">
    <div class="filter-bar">
      <el-input v-model="query.keyword" placeholder="题库标题" clearable style="width: 200px" @keyup.enter="loadData" />
      <el-select v-model="query.category_id" placeholder="分类" clearable style="width: 160px" @change="loadData">
        <el-option v-for="c in categories" :key="c.id" :label="c.name" :value="c.id" />
      </el-select>
      <el-select v-model="query.source_type" placeholder="来源" clearable style="width: 140px" @change="loadData">
        <el-option v-for="(v, k) in BANK_SOURCE_MAP" :key="k" :label="v" :value="Number(k)" />
      </el-select>
      <el-select v-model="query.status" placeholder="状态" clearable style="width: 140px" @change="loadData">
        <el-option v-for="(v, k) in BANK_STATUS_MAP" :key="k" :label="v.label" :value="Number(k)" />
      </el-select>
      <el-button type="primary" :icon="Search" @click="loadData">查询</el-button>
      <el-button :icon="Refresh" @click="resetQuery">重置</el-button>
      <el-button type="success" :icon="Plus" @click="openCreate">新建题库</el-button>
    </div>

    <el-table v-loading="loading" :data="list" border stripe>
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column label="题库" min-width="200">
        <template #default="{ row }">
          <div class="bank-name">{{ row.title }}</div>
          <div v-if="row.subtitle" class="bank-sub">{{ row.subtitle }}</div>
        </template>
      </el-table-column>
      <el-table-column prop="category_name" label="分类" width="120" />
      <el-table-column label="来源" width="100">
        <template #default="{ row }">{{ row.source_type_text }}</template>
      </el-table-column>
      <el-table-column label="收费" width="100">
        <template #default="{ row }">{{ row.charge_type_text }}</template>
      </el-table-column>
      <el-table-column prop="question_count" label="题目数" width="90" />
      <el-table-column prop="chapter_count" label="章节数" width="90" />
      <el-table-column label="状态" width="90">
        <template #default="{ row }">
          <el-tag :type="BANK_STATUS_MAP[row.status]?.type || 'info'">
            {{ BANK_STATUS_MAP[row.status]?.label || '未知' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="创建时间" width="170" />
      <el-table-column label="操作" width="240" fixed="right">
        <template #default="{ row }">
          <el-button link type="primary" @click="goQuestions(row)">题目</el-button>
          <el-button link type="primary" @click="openEdit(row)">编辑</el-button>
          <el-button link type="success" @click="handleExport(row)">导出</el-button>
          <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-pagination
      v-model:current-page="query.page"
      v-model:page-size="query.page_size"
      :total="total"
      :page-sizes="PAGE_SIZE_OPTIONS"
      layout="total, sizes, prev, pager, next"
      class="pager"
      @current-change="loadData"
      @size-change="loadData"
    />

    <!-- 新建/编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="isEdit ? '编辑题库' : '新建题库'" width="480px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="80px">
        <el-form-item label="标题" prop="title">
          <el-input v-model="form.title" placeholder="请输入题库标题" />
        </el-form-item>
        <el-form-item label="副标题">
          <el-input v-model="form.subtitle" placeholder="如：2026 版（选填）" />
        </el-form-item>
        <el-form-item label="分类">
          <el-select v-model="form.category_id" placeholder="选择分类（选填）" clearable style="width: 100%">
            <el-option v-for="c in categories" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="收费类型">
          <el-select v-model="form.charge_type" style="width: 100%">
            <el-option v-for="(v, k) in BANK_CHARGE_MAP" :key="k" :label="v" :value="Number(k)" />
          </el-select>
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
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { Search, Refresh, Plus } from '@element-plus/icons-vue'
import { fetchBankList, createBank, updateBank, deleteBank, exportBank, fetchBankCategories } from '@/api/bank'
import {
  BANK_SOURCE_MAP,
  BANK_STATUS_MAP,
  BANK_CHARGE_MAP,
  DEFAULT_PAGE_SIZE,
  PAGE_SIZE_OPTIONS,
} from '@/constants'
import type { BankItem, CategoryNode, BankCreatePayload } from '@/types/api.d'

const router = useRouter()
const loading = ref(false)
const submitting = ref(false)
const list = ref<BankItem[]>([])
const total = ref(0)
const categories = ref<CategoryNode[]>([])

const query = reactive({
  keyword: '',
  category_id: undefined as number | undefined,
  source_type: undefined as number | undefined,
  status: undefined as number | undefined,
  page: 1,
  page_size: DEFAULT_PAGE_SIZE,
})

const dialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref<number | null>(null)
const formRef = ref<FormInstance>()
const form = reactive<BankCreatePayload>({
  title: '',
  subtitle: '',
  category_id: undefined,
  charge_type: 1,
})
const rules: FormRules = {
  title: [{ required: true, message: '请输入题库标题', trigger: 'blur' }],
}

// 分类树拍平，供下拉选择使用
function flattenCategories(nodes: CategoryNode[]): CategoryNode[] {
  const out: CategoryNode[] = []
  nodes.forEach((n) => {
    out.push(n)
    if (n.children?.length) out.push(...flattenCategories(n.children))
  })
  return out
}

async function loadData() {
  loading.value = true
  try {
    const res = await fetchBankList({ ...query })
    list.value = res.list
    total.value = res.pagination.total
  } finally {
    loading.value = false
  }
}

function resetQuery() {
  query.keyword = ''
  query.category_id = undefined
  query.source_type = undefined
  query.status = undefined
  query.page = 1
  loadData()
}

function goQuestions(row: BankItem) {
  router.push(`/banks/${row.id}/questions`)
}

function openCreate() {
  isEdit.value = false
  editId.value = null
  form.title = ''
  form.subtitle = ''
  form.category_id = undefined
  form.charge_type = 1
  dialogVisible.value = true
}

function openEdit(row: BankItem) {
  isEdit.value = true
  editId.value = row.id
  form.title = row.title
  form.subtitle = row.subtitle
  form.category_id = row.category_id
  form.charge_type = row.charge_type
  dialogVisible.value = true
}

async function handleSubmit() {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    submitting.value = true
    try {
      if (isEdit.value && editId.value) {
        await updateBank(editId.value, { ...form })
        ElMessage.success('更新成功')
      } else {
        await createBank({ ...form })
        ElMessage.success('创建成功')
      }
      dialogVisible.value = false
      loadData()
    } finally {
      submitting.value = false
    }
  })
}

function handleDelete(row: BankItem) {
  ElMessageBox.confirm(`确定删除题库「${row.title}」吗？该题库下题目与章节将一并删除`, '提示', {
    type: 'warning',
  })
    .then(async () => {
      await deleteBank(row.id)
      ElMessage.success('删除成功')
      loadData()
    })
    .catch(() => {})
}

// 导出：拿 JSON 自己转 Blob 下载
async function handleExport(row: BankItem) {
  try {
    const data = await exportBank(row.id)
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `${data.bank.title || row.id}.json`
    a.click()
    URL.revokeObjectURL(url)
    ElMessage.success('导出成功')
  } catch {
    // 错误已由 request 拦截器统一提示
  }
}

onMounted(async () => {
  try {
    categories.value = flattenCategories(await fetchBankCategories())
  } catch {
    // 分类为可选项，失败不影响列表
  }
  loadData()
})
</script>

<style scoped lang="scss">
.bank-name {
  font-weight: 500;
  color: #1f2937;
}

.bank-sub {
  font-size: 12px;
  color: #9ca3af;
  margin-top: 2px;
}
</style>
