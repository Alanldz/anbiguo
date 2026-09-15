<template>
  <div class="page-container">
    <div class="filter-bar">
      <el-input v-model="query.keyword" placeholder="题干关键字" clearable style="width: 200px" @keyup.enter="loadData" />
      <el-select v-model="query.bank_id" placeholder="所属题库" clearable filterable style="width: 200px" @change="loadData">
        <el-option v-for="b in banks" :key="b.id" :label="b.title" :value="b.id" />
      </el-select>
      <el-button type="primary" :icon="Search" @click="loadData">查询</el-button>
      <el-button :icon="Refresh" @click="resetQuery">重置</el-button>
      <el-button
        type="danger"
        :icon="Delete"
        :disabled="selectedIds.length === 0"
        @click="handleBatchRemove"
      >
        批量移除
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
      <el-table-column label="题干" min-width="240" show-overflow-tooltip>
        <template #default="{ row }">{{ row.stem_preview }}</template>
      </el-table-column>
      <el-table-column prop="bank_title" label="所属题库" width="160" show-overflow-tooltip />
      <el-table-column label="题型" width="80">
        <template #default="{ row }">{{ row.question_type_text }}</template>
      </el-table-column>
      <el-table-column prop="wrong_count" label="错误次数" width="100" />
      <el-table-column prop="last_wrong_at" label="最近错误时间" width="170" />
      <el-table-column label="我的答案" width="120" show-overflow-tooltip>
        <template #default="{ row }">
          <span class="wrong-answer">{{ row.last_answer || '—' }}</span>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="120" fixed="right">
        <template #default="{ row }">
          <el-button link type="danger" @click="handleRemove(row)">移除</el-button>
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
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Refresh, Delete } from '@element-plus/icons-vue'
import { fetchWrongQuestions, removeWrongQuestion, batchRemoveWrongQuestions } from '@/api/wrong'
import { fetchBankList } from '@/api/bank'
import { DEFAULT_PAGE_SIZE, PAGE_SIZE_OPTIONS } from '@/constants'
import type { BankItem, WrongQuestionItem } from '@/types/api.d'

const loading = ref(false)
const list = ref<WrongQuestionItem[]>([])
const total = ref(0)
const banks = ref<BankItem[]>([])
const selectedIds = ref<number[]>([])

const query = reactive({
  keyword: '',
  bank_id: undefined as number | undefined,
  page: 1,
  page_size: DEFAULT_PAGE_SIZE,
})

function handleSelectionChange(rows: WrongQuestionItem[]) {
  selectedIds.value = rows.map((r) => r.id)
}

async function loadData() {
  loading.value = true
  try {
    const res = await fetchWrongQuestions({ ...query })
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

function handleRemove(row: WrongQuestionItem) {
  ElMessageBox.confirm('确定将该错题移出错题本吗？', '提示', { type: 'warning' })
    .then(async () => {
      await removeWrongQuestion(row.id)
      ElMessage.success('已移除')
      loadData()
    })
    .catch(() => {})
}

function handleBatchRemove() {
  ElMessageBox.confirm(`确定批量移除选中的 ${selectedIds.value.length} 条错题吗？`, '提示', { type: 'warning' })
    .then(async () => {
      await batchRemoveWrongQuestions({ ids: selectedIds.value })
      ElMessage.success('已移除')
      selectedIds.value = []
      loadData()
    })
    .catch(() => {})
}

onMounted(async () => {
  try {
    const res = await fetchBankList({ page: 1, page_size: 100 })
    banks.value = res.list
  } catch {
    // 忽略
  }
  loadData()
})
</script>

<style scoped lang="scss">
.wrong-answer {
  font-family: monospace;
  color: #f56c6c;
}
</style>
