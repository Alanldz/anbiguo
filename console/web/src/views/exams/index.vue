<template>
  <div class="page-container">
    <div class="filter-bar">
      <el-input v-model="query.keyword" placeholder="试卷名" clearable style="width: 200px" @keyup.enter="loadData" />
      <el-select v-model="query.bank_id" placeholder="所属题库" clearable filterable style="width: 200px" @change="loadData">
        <el-option v-for="b in banks" :key="b.id" :label="b.title" :value="b.id" />
      </el-select>
      <el-button type="primary" :icon="Search" @click="loadData">查询</el-button>
      <el-button :icon="Refresh" @click="resetQuery">重置</el-button>
    </div>

    <el-table v-loading="loading" :data="list" border stripe>
      <el-table-column prop="record_no" label="记录号" width="180" />
      <el-table-column prop="paper_title" label="试卷名" min-width="160" show-overflow-tooltip />
      <el-table-column prop="bank_title" label="题库" width="140" show-overflow-tooltip />
      <el-table-column prop="total_count" label="题数" width="80" />
      <el-table-column prop="correct_rate" label="正确率" width="90" />
      <el-table-column label="得分" width="110">
        <template #default="{ row }">{{ row.get_score }} / {{ row.total_score }}</template>
      </el-table-column>
      <el-table-column label="耗时" width="100">
        <template #default="{ row }">{{ formatDuration(row.duration_seconds) }}</template>
      </el-table-column>
      <el-table-column label="是否及格" width="100">
        <template #default="{ row }">
          <el-tag :type="row.is_passed ? 'success' : 'danger'" size="small">
            {{ row.is_passed ? '及格' : '不及格' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="状态" width="90">
        <template #default="{ row }">
          <el-tag :type="EXAM_STATUS_MAP[row.status]?.type || 'info'" size="small">
            {{ EXAM_STATUS_MAP[row.status]?.label }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="submitted_at" label="交卷时间" width="170">
        <template #default="{ row }">{{ row.submitted_at || '—' }}</template>
      </el-table-column>
      <el-table-column label="操作" width="100" fixed="right">
        <template #default="{ row }">
          <el-button link type="primary" @click="openDetail(row)">详情</el-button>
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

    <!-- 详情抽屉：含作答明细 -->
    <el-drawer v-model="detailVisible" title="考试作答明细" size="560px">
      <template v-if="detail">
        <el-descriptions :column="1" border>
          <el-descriptions-item label="试卷">{{ detail.paper_title }}</el-descriptions-item>
          <el-descriptions-item label="题库">{{ detail.bank_title }}</el-descriptions-item>
          <el-descriptions-item label="题数">{{ detail.total_count }}</el-descriptions-item>
          <el-descriptions-item label="正确率">{{ detail.correct_rate }}</el-descriptions-item>
          <el-descriptions-item label="得分">{{ detail.get_score }} / {{ detail.total_score }}</el-descriptions-item>
          <el-descriptions-item label="是否及格">
            <el-tag :type="detail.is_passed ? 'success' : 'danger'">
              {{ detail.is_passed ? '及格' : '不及格' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="耗时">{{ formatDuration(detail.duration_seconds) }}</el-descriptions-item>
          <el-descriptions-item label="交卷时间">{{ detail.submitted_at || '—' }}</el-descriptions-item>
        </el-descriptions>

        <div class="exam-drawer__title">作答明细（{{ detail.answers.length }}）</div>
        <el-empty v-if="detail.answers.length === 0" description="暂无作答明细" />
        <div v-for="(a, idx) in detail.answers" :key="idx" class="exam-answer">
          <div class="exam-answer__head">
            <span class="exam-answer__idx">{{ idx + 1 }}.</span>
            <span class="exam-answer__stem">{{ a.stem_preview }}</span>
            <el-tag :type="a.is_correct ? 'success' : 'danger'" size="small">
              {{ a.is_correct ? '正确' : '错误' }}
            </el-tag>
          </div>
          <div class="exam-answer__row">我的答案：<span class="exam-answer__me">{{ a.user_answer || '未答' }}</span></div>
          <div class="exam-answer__row">得分：{{ a.score }}</div>
        </div>
      </template>
    </el-drawer>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { Search, Refresh } from '@element-plus/icons-vue'
import { fetchExamRecords, fetchExamRecordDetail } from '@/api/exam'
import { fetchBankList } from '@/api/bank'
import { EXAM_STATUS_MAP, DEFAULT_PAGE_SIZE, PAGE_SIZE_OPTIONS } from '@/constants'
import type { BankItem, ExamRecordItem, ExamRecordDetail } from '@/types/api.d'

const loading = ref(false)
const list = ref<ExamRecordItem[]>([])
const total = ref(0)
const banks = ref<BankItem[]>([])

const query = reactive({
  keyword: '',
  bank_id: undefined as number | undefined,
  page: 1,
  page_size: DEFAULT_PAGE_SIZE,
})

const detailVisible = ref(false)
const detail = ref<ExamRecordDetail | null>(null)

function formatDuration(seconds: number): string {
  const h = Math.floor(seconds / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  if (h > 0) return m > 0 ? `${h}小时${m}分` : `${h}小时`
  if (m > 0) return `${m}分`
  return `${seconds}秒`
}

async function openDetail(row: ExamRecordItem) {
  try {
    detail.value = await fetchExamRecordDetail(row.id)
    detailVisible.value = true
  } catch {
    // 错误已由 request 拦截器统一提示
  }
}

async function loadData() {
  loading.value = true
  try {
    const res = await fetchExamRecords({ ...query })
    list.value = res.list
    total.value = res.pagination.total
  } finally {
    loading.value = false
  }
}

function resetQuery() {
  query.keyword = ''
  query.bank_id = undefined
  query.page = 1
  loadData()
}

onMounted(async () => {
  try {
    const res = await fetchBankList({ page: 1, page_size: 100 })
    banks.value = res.list
  } catch {
    // 题库下拉为可选项，失败不影响列表
  }
  loadData()
})
</script>

<style scoped lang="scss">
.exam-drawer__title {
  margin: 16px 0 8px;
  font-weight: 600;
}

.exam-answer {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 12px;

  &__head {
    display: flex;
    gap: 8px;
    align-items: flex-start;
  }

  &__idx {
    font-weight: 600;
  }

  &__stem {
    flex: 1;
    font-weight: 500;
  }

  &__row {
    margin-top: 6px;
    font-size: 13px;
    color: #6b7280;
  }

  &__me {
    color: #f56c6c;
    font-family: monospace;
  }
}
</style>
