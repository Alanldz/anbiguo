<template>
  <div class="page-container">
    <div class="filter-bar">
      <el-select v-model="query.status" placeholder="状态" clearable style="width: 150px" @change="loadData">
        <el-option v-for="(v, k) in IMPORT_TASK_STATUS_MAP" :key="k" :label="v.label" :value="Number(k)" />
      </el-select>
      <el-button type="primary" :icon="Search" @click="loadData">查询</el-button>
      <el-button type="success" :icon="Plus" @click="openCreate">创建导入任务</el-button>
    </div>

    <el-table v-loading="loading" :data="list" border stripe>
      <el-table-column prop="task_no" label="任务号" width="170" />
      <el-table-column prop="bank_title" label="目标题库" min-width="140" show-overflow-tooltip />
      <el-table-column prop="origin_name" label="文件名" min-width="160" show-overflow-tooltip />
      <el-table-column label="导入方式" width="110">
        <template #default="{ row }">{{ row.import_mode_text }}</template>
      </el-table-column>
      <el-table-column label="进度" width="200">
        <template #default="{ row }">
          <el-progress :percentage="row.progress || 0" :status="row.status === 5 ? 'exception' : undefined" />
        </template>
      </el-table-column>
      <el-table-column label="状态" width="90">
        <template #default="{ row }">
          <el-tag :type="IMPORT_TASK_STATUS_MAP[row.status]?.type || 'info'">
            {{ IMPORT_TASK_STATUS_MAP[row.status]?.label }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="创建时间" width="170" />
      <el-table-column label="操作" width="160" fixed="right">
        <template #default="{ row }">
          <el-button link type="primary" @click="openDetail(row)">详情</el-button>
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

    <!-- 创建任务弹窗 -->
    <el-dialog v-model="createVisible" title="创建导入任务" width="520px">
      <el-alert v-if="templateColumns.length" type="info" :closable="false" class="import-tip">
        <template #title>文档需包含以下列</template>
        <div v-for="col in templateColumns" :key="col.name" class="import-tip__col">
          {{ col.name }}<em v-if="col.required">（必填）</em> —— {{ col.desc }}
        </div>
      </el-alert>
      <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
        <el-form-item label="选择文件" prop="origin_name">
          <el-upload
            class="import-upload"
            drag
            :auto-upload="false"
            :show-file-list="true"
            :limit="1"
            :on-change="onFileChange"
            :on-remove="onFileRemove"
          >
            <el-icon class="el-icon--upload"><UploadFilled /></el-icon>
            <div class="el-upload__text">将文件拖到此处，或<em>点击选择</em></div>
            <template #tip>
              <div class="el-upload__tip">支持 docx / pdf / txt 等（真实直传七牛暂未接入）</div>
            </template>
          </el-upload>
        </el-form-item>
        <el-form-item label="导入方式">
          <el-select v-model="form.import_mode" style="width: 100%">
            <el-option v-for="(v, k) in IMPORT_MODE_MAP" :key="k" :label="v" :value="Number(k)" />
          </el-select>
        </el-form-item>
        <el-form-item label="目标题库">
          <el-radio-group v-model="bankMode">
            <el-radio :value="'existing'">已有题库</el-radio>
            <el-radio :value="'new'">新建题库</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-if="bankMode === 'existing'" label="选择题库" prop="bank_id">
          <el-select v-model="form.bank_id" placeholder="选择题库" filterable clearable style="width: 100%">
            <el-option v-for="b in banks" :key="b.id" :label="b.title" :value="b.id" />
          </el-select>
        </el-form-item>
        <el-form-item v-else label="新题库名" prop="bank_title">
          <el-input v-model="form.bank_title" placeholder="输入新题库名称" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="createVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="handleCreate">创建任务</el-button>
      </template>
    </el-dialog>

    <!-- 详情抽屉 -->
    <el-drawer v-model="detailVisible" title="导入任务详情" size="560px">
      <template v-if="detail">
        <el-descriptions :column="1" border>
          <el-descriptions-item label="任务号">{{ detail.task_no }}</el-descriptions-item>
          <el-descriptions-item label="文件名">{{ detail.origin_name }}</el-descriptions-item>
          <el-descriptions-item label="导入方式">{{ detail.import_mode_text }}</el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-tag :type="IMPORT_TASK_STATUS_MAP[detail.status]?.type || 'info'">
              {{ IMPORT_TASK_STATUS_MAP[detail.status]?.label }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="进度">{{ detail.progress }}%</el-descriptions-item>
          <el-descriptions-item label="解析/成功/失败">
            {{ detail.total_count }} / {{ detail.success_count }} / {{ detail.fail_count }}
          </el-descriptions-item>
          <el-descriptions-item v-if="detail.error_message" label="失败原因">
            <span class="import-error">{{ detail.error_message }}</span>
          </el-descriptions-item>
        </el-descriptions>

        <div class="import-drawer__title">待校对题目（{{ detail.result.length }}）</div>
        <el-empty v-if="detail.result.length === 0" description="暂无待校对题目" />
        <div v-for="(q, idx) in detail.result" :key="idx" class="import-q">
          <div class="import-q__head">
            <el-tag size="small">{{ QUESTION_TYPE_MAP[q.question_type] }}</el-tag>
            <span class="import-q__stem">{{ q.stem }}</span>
          </div>
          <div v-if="q.options?.length" class="import-q__opts">
            <div v-for="o in q.options" :key="o.option_key" class="import-q__opt">
              {{ o.option_key }}. {{ o.content }}
              <el-tag v-if="o.is_correct === 1" size="small" type="success">正确</el-tag>
            </div>
          </div>
          <div v-if="q.answer" class="import-q__answer">答案：{{ q.answer }}</div>
        </div>
      </template>
    </el-drawer>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules, type UploadFile } from 'element-plus'
import { Search, Plus, UploadFilled } from '@element-plus/icons-vue'
import {
  fetchImportTasks,
  createImportTask,
  fetchImportTaskDetail,
  deleteImportTask,
  fetchImportTemplate,
} from '@/api/import'
import { fetchBankList } from '@/api/bank'
import {
  IMPORT_TASK_STATUS_MAP,
  IMPORT_MODE_MAP,
  QUESTION_TYPE_MAP,
  DEFAULT_PAGE_SIZE,
  PAGE_SIZE_OPTIONS,
} from '@/constants'
import type { BankItem, ImportTaskItem, ImportTaskDetail, ImportTemplateColumn } from '@/types/api.d'

const loading = ref(false)
const submitting = ref(false)
const list = ref<ImportTaskItem[]>([])
const total = ref(0)
const banks = ref<BankItem[]>([])
const templateColumns = ref<ImportTemplateColumn[]>([])

const query = reactive({
  status: undefined as number | undefined,
  page: 1,
  page_size: DEFAULT_PAGE_SIZE,
})

const createVisible = ref(false)
const formRef = ref<FormInstance>()
const bankMode = ref<'existing' | 'new'>('existing')
const form = reactive({
  origin_name: '',
  file_ext: '',
  file_id: '',
  import_mode: 1,
  bank_id: undefined as number | undefined,
  bank_title: '',
})
const rules: FormRules = {
  origin_name: [{ required: true, message: '请选择文件', trigger: 'change' }],
  bank_id: [{ required: true, message: '请选择题库', trigger: 'change' }],
  bank_title: [{ required: true, message: '请输入新题库名称', trigger: 'blur' }],
}

const detailVisible = ref(false)
const detail = ref<ImportTaskDetail | null>(null)

function onFileChange(file: UploadFile) {
  form.origin_name = file.name
  form.file_ext = (file.name.split('.').pop() || '').toLowerCase()
  // TODO: 真实直传七牛流程
  // 1) 调 fetchUploadToken 取 upload_token / object_key
  // 2) 前端直传七牛（PUT upload_url）
  // 3) 用返回的 object_key 作为此处 file_id
  // 当前为同步占位：以文件名生成临时 file_id，后端写入占位结构
  form.file_id = `temp_${Date.now()}_${file.name}`
}

function onFileRemove() {
  form.origin_name = ''
  form.file_ext = ''
  form.file_id = ''
}

function openCreate() {
  form.origin_name = ''
  form.file_ext = ''
  form.file_id = ''
  form.import_mode = 1
  bankMode.value = 'existing'
  form.bank_id = undefined
  form.bank_title = ''
  createVisible.value = true
}

async function handleCreate() {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    if (!form.file_id) {
      ElMessage.warning('请先选择文件')
      return
    }
    submitting.value = true
    try {
      await createImportTask({
        file_id: form.file_id,
        import_mode: form.import_mode,
        bank_id: bankMode.value === 'existing' ? form.bank_id : 0,
        bank_title: bankMode.value === 'new' ? form.bank_title : undefined,
      })
      ElMessage.success('任务已创建，等待校对')
      createVisible.value = false
      loadData()
    } finally {
      submitting.value = false
    }
  })
}

async function openDetail(row: ImportTaskItem) {
  try {
    detail.value = await fetchImportTaskDetail(row.id)
    detailVisible.value = true
  } catch {
    // 错误已统一提示
  }
}

async function handleDelete(row: ImportTaskItem) {
  ElMessageBox.confirm(`确定删除任务「${row.task_no}」吗？`, '提示', { type: 'warning' })
    .then(async () => {
      await deleteImportTask(row.id)
      ElMessage.success('删除成功')
      loadData()
    })
    .catch(() => {})
}

async function loadData() {
  loading.value = true
  try {
    const res = await fetchImportTasks({ ...query })
    list.value = res.list
    total.value = res.pagination.total
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  try {
    const res = await fetchBankList({ page: 1, page_size: 100 })
    banks.value = res.list
  } catch {
    // 题库下拉为可选项，失败不影响列表
  }
  try {
    const tpl = await fetchImportTemplate()
    templateColumns.value = tpl.columns
  } catch {
    // 模板说明为可选项
  }
  loadData()
})
</script>

<style scoped lang="scss">
.import-upload {
  width: 100%;
}

.import-tip {
  margin-bottom: 12px;

  &__col {
    font-size: 12px;
    line-height: 1.8;

    em {
      color: #f56c6c;
      font-style: normal;
    }
  }
}

.import-error {
  color: #f56c6c;
}

.import-drawer__title {
  margin: 16px 0 8px;
  font-weight: 600;
}

.import-q {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 12px;

  &__head {
    display: flex;
    gap: 8px;
    align-items: flex-start;
  }

  &__stem {
    font-weight: 500;
  }

  &__opts {
    margin-top: 8px;
    padding-left: 4px;
  }

  &__opt {
    font-size: 13px;
    line-height: 1.9;
  }

  &__answer {
    margin-top: 6px;
    color: var(--el-color-primary);
  }
}
</style>
