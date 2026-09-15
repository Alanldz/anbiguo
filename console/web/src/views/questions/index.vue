<template>
  <div class="page-container">
    <el-page-header class="q-header" title="返回题库" content="" @back="goBack">
      <template #content>
        <span class="q-header__title">题目管理</span>
        <span v-if="bankTitle" class="q-header__bank">· {{ bankTitle }}</span>
      </template>
    </el-page-header>

    <div class="q-layout">
      <!-- 左侧章节管理 -->
      <el-card class="q-chapters" shadow="never">
        <template #header>
          <div class="q-chapters__head">
            <span>章节</span>
            <el-button type="primary" link :icon="Plus" @click="openChapterCreate()">新增</el-button>
          </div>
        </template>
        <div v-loading="chapterLoading" class="q-chapters__list">
          <div
            v-for="ch in chapters"
            :key="ch.id"
            class="q-chapters__item"
            :class="{ 'is-active': query.chapter_id === ch.id }"
            @click="toggleChapterFilter(ch.id)"
          >
            <span class="q-chapters__name">
              {{ ch.name }}
              <em class="q-chapters__count">{{ ch.question_count }}</em>
            </span>
            <span class="q-chapters__ops">
              <el-icon title="编辑" @click.stop="openChapterEdit(ch)"><Edit /></el-icon>
              <el-icon title="删除" @click.stop="handleChapterDelete(ch)"><Delete /></el-icon>
            </span>
          </div>
          <el-empty v-if="!chapterLoading && chapters.length === 0" description="暂无章节" :image-size="50" />
        </div>
      </el-card>

      <!-- 右侧题目区 -->
      <el-card class="q-main" shadow="never">
        <div class="filter-bar">
          <el-input v-model="query.keyword" placeholder="题干关键字" clearable style="width: 200px" @keyup.enter="loadData" />
          <el-select v-model="query.question_type" placeholder="题型" clearable style="width: 130px" @change="loadData">
            <el-option v-for="(v, k) in QUESTION_TYPE_MAP" :key="k" :label="v" :value="Number(k)" />
          </el-select>
          <el-select v-model="query.difficulty" placeholder="难度" clearable style="width: 120px" @change="loadData">
            <el-option v-for="(v, k) in QUESTION_DIFFICULTY_MAP" :key="k" :label="v.label" :value="Number(k)" />
          </el-select>
          <el-button type="primary" :icon="Search" @click="loadData">查询</el-button>
          <el-button :icon="Refresh" @click="resetQuery">重置</el-button>
          <el-button type="success" :icon="Plus" @click="openQuestionCreate">新增题目</el-button>
          <el-button
            type="danger"
            :icon="Delete"
            :disabled="selectedIds.length === 0"
            @click="handleBatchDelete"
          >
            批量删除
          </el-button>
          <el-button
            type="warning"
            :icon="Sort"
            :disabled="selectedIds.length === 0"
            @click="moveDialogVisible = true"
          >
            移入章节
          </el-button>
        </div>

        <el-table
          v-loading="loading"
          :data="list"
          border
          stripe
          @selection-change="handleSelectionChange"
        >
          <el-table-column type="selection" width="46" />
          <el-table-column prop="id" label="ID" width="80" />
          <el-table-column label="题型" width="80">
            <template #default="{ row }">{{ QUESTION_TYPE_MAP[row.question_type] }}</template>
          </el-table-column>
          <el-table-column label="题干" min-width="220" show-overflow-tooltip>
            <template #default="{ row }">{{ row.stem_preview }}</template>
          </el-table-column>
          <el-table-column prop="chapter_name" label="章节" width="140" show-overflow-tooltip />
          <el-table-column label="难度" width="80">
            <template #default="{ row }">
              <el-tag :type="QUESTION_DIFFICULTY_MAP[row.difficulty]?.type || 'info'" size="small">
                {{ QUESTION_DIFFICULTY_MAP[row.difficulty]?.label }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="score" label="分值" width="80" />
          <el-table-column prop="correct_rate" label="正确率" width="90" />
          <el-table-column label="操作" width="150" fixed="right">
            <template #default="{ row }">
              <el-button link type="primary" @click="openQuestionEdit(row)">编辑</el-button>
              <el-button link type="danger" @click="handleQuestionDelete(row)">删除</el-button>
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
      </el-card>
    </div>

    <!-- 章节编辑弹窗 -->
    <el-dialog v-model="chapterDialogVisible" :title="chapterIsEdit ? '编辑章节' : '新增章节'" width="420px">
      <el-form label-width="70px">
        <el-form-item label="名称" required>
          <el-input v-model="chapterForm.name" placeholder="请输入章节名称" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="chapterForm.sort_order" :min="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="chapterDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="chapterSubmitting" @click="handleChapterSubmit">确定</el-button>
      </template>
    </el-dialog>

    <!-- 题目编辑弹窗 -->
    <el-dialog v-model="qDialogVisible" :title="qIsEdit ? '编辑题目' : '新增题目'" width="640px">
      <el-form ref="qFormRef" :model="qForm" :rules="qRules" label-width="80px">
        <el-form-item label="题型" prop="question_type">
          <el-select v-model="qForm.question_type" style="width: 100%" @change="onTypeChange">
            <el-option v-for="(v, k) in QUESTION_TYPE_MAP" :key="k" :label="v" :value="Number(k)" />
          </el-select>
        </el-form-item>
        <el-form-item label="题干" prop="stem">
          <el-input v-model="qForm.stem" type="textarea" :rows="3" placeholder="请输入题干" />
        </el-form-item>
        <el-form-item label="章节">
          <el-select v-model="qForm.chapter_id" placeholder="未分类" clearable style="width: 100%">
            <el-option v-for="ch in chapters" :key="ch.id" :label="ch.name" :value="ch.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="难度">
          <el-select v-model="qForm.difficulty" style="width: 100%">
            <el-option v-for="(v, k) in QUESTION_DIFFICULTY_MAP" :key="k" :label="v.label" :value="Number(k)" />
          </el-select>
        </el-form-item>
        <el-form-item label="分值">
          <el-input-number v-model="qForm.score" :min="0" :precision="2" :step="0.5" />
        </el-form-item>

        <!-- 选项区：单选 / 多选 / 判断 -->
        <template v-if="showOptions">
          <el-form-item label="选项">
            <div class="q-options">
              <div v-for="(opt, idx) in qForm.options" :key="idx" class="q-options__row">
                <span class="q-options__key">{{ opt.option_key }}</span>
                <el-input v-model="opt.content" placeholder="选项内容" />
                <el-button
                  v-if="qForm.options.length > 1"
                  type="danger"
                  link
                  :icon="Minus"
                  @click="removeOption(idx)"
                />
              </div>
              <el-button type="primary" link :icon="Plus" @click="addOption">添加选项</el-button>
            </div>
          </el-form-item>
          <el-form-item label="正确答案">
            <el-radio-group v-if="qForm.question_type !== 2" v-model="qAnswer">
              <el-radio v-for="opt in qForm.options" :key="opt.option_key" :value="opt.option_key">
                {{ opt.option_key }}
              </el-radio>
            </el-radio-group>
            <el-checkbox-group v-else v-model="qAnswerMulti">
              <el-checkbox v-for="opt in qForm.options" :key="opt.option_key" :value="opt.option_key">
                {{ opt.option_key }}
              </el-checkbox>
            </el-checkbox-group>
          </el-form-item>
        </template>

        <!-- 填空题 / 简答题：直接填参考答案 -->
        <el-form-item v-else label="答案">
          <el-input v-model="qForm.answer" type="textarea" :rows="2" placeholder="请输入参考答案" />
        </el-form-item>

        <el-form-item label="解析">
          <el-input v-model="qForm.analysis" type="textarea" :rows="2" placeholder="解析（选填）" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="qDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="qSubmitting" @click="handleQuestionSubmit">确定</el-button>
      </template>
    </el-dialog>

    <!-- 批量移动章节弹窗 -->
    <el-dialog v-model="moveDialogVisible" title="批量移入章节" width="420px">
      <el-form label-width="70px">
        <el-form-item label="目标章节" required>
          <el-select v-model="moveChapterId" placeholder="选择章节" clearable style="width: 100%">
            <el-option v-for="ch in chapters" :key="ch.id" :label="ch.name" :value="ch.id" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="moveDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="qSubmitting" @click="handleBatchMove">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { Search, Refresh, Plus, Delete, Edit, Minus, Sort } from '@element-plus/icons-vue'
import {
  fetchChapters,
  createChapter,
  updateChapter,
  deleteChapter,
} from '@/api/chapter'
import {
  fetchQuestions,
  fetchQuestionDetail,
  createQuestion,
  updateQuestion,
  deleteQuestion,
  batchDeleteQuestions,
  batchMoveQuestions,
} from '@/api/question'
import { fetchBankDetail } from '@/api/bank'
import {
  QUESTION_TYPE_MAP,
  QUESTION_DIFFICULTY_MAP,
  DEFAULT_PAGE_SIZE,
  PAGE_SIZE_OPTIONS,
} from '@/constants'
import type {
  BankItem,
  ChapterItem,
  QuestionItem,
  QuestionOption,
  QuestionCreatePayload,
} from '@/types/api.d'

const route = useRoute()
const router = useRouter()
const bankId = computed(() => Number(route.params.id))

const bankTitle = ref('')
const chapters = ref<ChapterItem[]>([])
const chapterLoading = ref(false)

const loading = ref(false)
const list = ref<QuestionItem[]>([])
const total = ref(0)
const selectedIds = ref<number[]>([])

const query = reactive({
  keyword: '',
  question_type: undefined as number | undefined,
  difficulty: undefined as number | undefined,
  chapter_id: undefined as number | undefined,
  page: 1,
  page_size: DEFAULT_PAGE_SIZE,
})

// ---- 章节弹窗 ----
const chapterDialogVisible = ref(false)
const chapterIsEdit = ref(false)
const chapterEditId = ref<number | null>(null)
const chapterSubmitting = ref(false)
const chapterForm = reactive({ name: '', sort_order: 0 })

function openChapterCreate() {
  chapterIsEdit.value = false
  chapterEditId.value = null
  chapterForm.name = ''
  chapterForm.sort_order = chapters.value.length
  chapterDialogVisible.value = true
}

function openChapterEdit(ch: ChapterItem) {
  chapterIsEdit.value = true
  chapterEditId.value = ch.id
  chapterForm.name = ch.name
  chapterForm.sort_order = ch.sort_order
  chapterDialogVisible.value = true
}

async function handleChapterSubmit() {
  if (!chapterForm.name.trim()) {
    ElMessage.warning('请输入章节名称')
    return
  }
  chapterSubmitting.value = true
  try {
    if (chapterIsEdit.value && chapterEditId.value) {
      await updateChapter(chapterEditId.value, { ...chapterForm })
      ElMessage.success('更新成功')
    } else {
      await createChapter(bankId.value, { ...chapterForm })
      ElMessage.success('新增成功')
    }
    chapterDialogVisible.value = false
    await loadChapters()
  } finally {
    chapterSubmitting.value = false
  }
}

function handleChapterDelete(ch: ChapterItem) {
  ElMessageBox.confirm(`确定删除章节「${ch.name}」吗？该章节下题目将变为未分类`, '提示', { type: 'warning' })
    .then(async () => {
      await deleteChapter(ch.id)
      ElMessage.success('删除成功')
      await loadChapters()
      loadData()
    })
    .catch(() => {})
}

// 点击章节：切换筛选（再次点击取消）
function toggleChapterFilter(id: number) {
  query.chapter_id = query.chapter_id === id ? undefined : id
  query.page = 1
  loadData()
}

// ---- 题目弹窗 ----
const qDialogVisible = ref(false)
const qIsEdit = ref(false)
const qEditId = ref<number | null>(null)
const qSubmitting = ref(false)
const qFormRef = ref<FormInstance>()
const qAnswer = ref<string>('')
const qAnswerMulti = ref<string[]>([])

function defaultOptions(): QuestionOption[] {
  return [
    { option_key: 'A', content: '', is_correct: 0 },
    { option_key: 'B', content: '', is_correct: 0 },
  ]
}

const qForm = reactive<QuestionCreatePayload>({
  question_type: 1,
  stem: '',
  analysis: '',
  answer: '',
  difficulty: 1,
  score: 2,
  chapter_id: 0,
  options: defaultOptions(),
})

// 单选 / 多选 / 判断需要选项区；填空 / 简答直接填答案
const showOptions = computed(() => [1, 2, 3].includes(qForm.question_type))

const qRules: FormRules = {
  question_type: [{ required: true, message: '请选择题型', trigger: 'change' }],
  stem: [{ required: true, message: '请输入题干', trigger: 'blur' }],
}

// 选项标号按位置自动重排（A、B、C...）
function syncOptionKeys() {
  qForm.options.forEach((opt, idx) => {
    opt.option_key = String.fromCharCode(65 + idx)
  })
}

function addOption() {
  qForm.options.push({ option_key: '', content: '', is_correct: 0 })
  syncOptionKeys()
}

function removeOption(idx: number) {
  qForm.options.splice(idx, 1)
  syncOptionKeys()
}

function onTypeChange(type: number) {
  if ([1, 2, 3].includes(type) && qForm.options.length === 0) {
    qForm.options = defaultOptions()
  }
  // 切换题型时清空旧答案选择
  qAnswer.value = ''
  qAnswerMulti.value = []
}

function openQuestionCreate() {
  qIsEdit.value = false
  qEditId.value = null
  qForm.question_type = 1
  qForm.stem = ''
  qForm.analysis = ''
  qForm.answer = ''
  qForm.difficulty = 1
  qForm.score = 2
  qForm.chapter_id = query.chapter_id || 0
  qForm.options = defaultOptions()
  qAnswer.value = ''
  qAnswerMulti.value = []
  qDialogVisible.value = true
}

async function openQuestionEdit(row: QuestionItem) {
  const detail = await fetchQuestionDetail(row.id)
  qIsEdit.value = true
  qEditId.value = row.id
  qForm.question_type = detail.question_type
  qForm.stem = detail.stem
  qForm.analysis = detail.analysis
  qForm.difficulty = detail.difficulty
  qForm.score = Number(detail.score)
  qForm.chapter_id = detail.chapter_id
  qForm.options = detail.options.length
    ? detail.options.map((o) => ({ option_key: o.option_key, content: o.content, is_correct: o.is_correct }))
    : defaultOptions()
  // 回显正确答案：单选用单值，多选用数组
  if (detail.question_type === 2) {
    qAnswerMulti.value = detail.answer ? detail.answer.split(',') : []
    qAnswer.value = ''
  } else if ([1, 3].includes(detail.question_type)) {
    qAnswer.value = detail.answer
    qAnswerMulti.value = []
  } else {
    qForm.answer = detail.answer
  }
  qDialogVisible.value = true
}

// 组装提交数据：把选项勾选状态与 answer 字段对齐
function buildSubmitPayload(): QuestionCreatePayload {
  const payload: QuestionCreatePayload = { ...qForm }
  if ([1, 2, 3].includes(qForm.question_type)) {
    if (qForm.question_type === 2) {
      payload.answer = qAnswerMulti.value.join(',')
      qForm.options.forEach((o) => {
        o.is_correct = qAnswerMulti.value.includes(o.option_key) ? 1 : 0
      })
    } else {
      payload.answer = qAnswer.value
      qForm.options.forEach((o) => {
        o.is_correct = o.option_key === qAnswer.value ? 1 : 0
      })
    }
  }
  return payload
}

async function handleQuestionSubmit() {
  if (!qFormRef.value) return
  await qFormRef.value.validate(async (valid) => {
    if (!valid) return
    if (showOptions.value) {
      const filled = qForm.options.filter((o) => o.content.trim())
      if (filled.length < 2) {
        ElMessage.warning('请至少填写两个选项内容')
        return
      }
      if (qForm.question_type === 2 && qAnswerMulti.value.length === 0) {
        ElMessage.warning('请选择正确答案')
        return
      }
      if (qForm.question_type !== 2 && !qAnswer.value) {
        ElMessage.warning('请选择正确答案')
        return
      }
    }
    qSubmitting.value = true
    try {
      const payload = buildSubmitPayload()
      if (qIsEdit.value && qEditId.value) {
        await updateQuestion(qEditId.value, payload)
        ElMessage.success('更新成功')
      } else {
        await createQuestion(bankId.value, payload)
        ElMessage.success('新增成功')
      }
      qDialogVisible.value = false
      await loadChapters()
      loadData()
    } finally {
      qSubmitting.value = false
    }
  })
}

function handleSelectionChange(rows: QuestionItem[]) {
  selectedIds.value = rows.map((r) => r.id)
}

function handleQuestionDelete(row: QuestionItem) {
  ElMessageBox.confirm('确定删除该题目吗？', '提示', { type: 'warning' })
    .then(async () => {
      await deleteQuestion(row.id)
      ElMessage.success('删除成功')
      loadData()
    })
    .catch(() => {})
}

function handleBatchDelete() {
  ElMessageBox.confirm(`确定批量删除选中的 ${selectedIds.value.length} 道题目吗？`, '提示', { type: 'warning' })
    .then(async () => {
      await batchDeleteQuestions({ ids: selectedIds.value })
      ElMessage.success('删除成功')
      selectedIds.value = []
      loadData()
    })
    .catch(() => {})
}

// 批量移动章节
const moveDialogVisible = ref(false)
const moveChapterId = ref<number | undefined>(undefined)

async function handleBatchMove() {
  if (!moveChapterId.value) {
    ElMessage.warning('请选择目标章节')
    return
  }
  qSubmitting.value = true
  try {
    await batchMoveQuestions({ ids: selectedIds.value, chapter_id: moveChapterId.value })
    ElMessage.success('移动成功')
    moveDialogVisible.value = false
    selectedIds.value = []
    await loadChapters()
    loadData()
  } finally {
    qSubmitting.value = false
  }
}

// ---- 数据加载 ----
async function loadChapters() {
  chapterLoading.value = true
  try {
    chapters.value = await fetchChapters(bankId.value)
  } finally {
    chapterLoading.value = false
  }
}

async function loadData() {
  loading.value = true
  try {
    const res = await fetchQuestions(bankId.value, { ...query })
    list.value = res.list
    total.value = res.pagination.total
  } finally {
    loading.value = false
  }
}

function resetQuery() {
  query.keyword = ''
  query.question_type = undefined
  query.difficulty = undefined
  query.chapter_id = undefined
  query.page = 1
  loadData()
}

function goBack() {
  router.push('/banks')
}

onMounted(async () => {
  try {
    const bank = await fetchBankDetail(bankId.value)
    bankTitle.value = (bank as BankItem).title
  } catch {
    // 题库信息获取失败不影响题目列表
  }
  loadChapters()
  loadData()
})
</script>

<style scoped lang="scss">
.q-header {
  margin-bottom: 16px;

  &__title {
    font-weight: 600;
  }

  &__bank {
    color: #6b7280;
    margin-left: 4px;
  }
}

.q-layout {
  display: flex;
  gap: 16px;
  align-items: flex-start;
}

.q-chapters {
  width: 240px;
  flex-shrink: 0;

  &__head {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  &__list {
    min-height: 200px;
  }

  &__item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 10px;
    border-radius: 6px;
    cursor: pointer;

    &:hover {
      background: #f3f4f6;
    }

    &.is-active {
      background: var(--el-color-primary-light-9);
      color: var(--el-color-primary);
    }
  }

  &__name {
    font-size: 14px;
  }

  &__count {
    font-style: normal;
    font-size: 12px;
    color: #9ca3af;
    margin-left: 6px;
  }

  &__ops {
    display: none;
    gap: 8px;

    .q-chapters__item:hover & {
      display: flex;
    }
  }
}

.q-main {
  flex: 1;
  min-width: 0;
}

.q-options {
  width: 100%;

  &__row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
  }

  &__key {
    width: 24px;
    text-align: center;
    font-weight: 600;
    color: var(--el-color-primary);
  }
}
</style>
