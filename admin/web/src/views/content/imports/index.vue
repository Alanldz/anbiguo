<template>
  <div class="page-container">
    <div class="filter-bar">
      <el-select v-model="query.status" placeholder="状态" clearable style="width: 160px" @change="loadData">
        <el-option v-for="(v, k) in IMPORT_TASK_STATUS_MAP" :key="k" :label="v.label" :value="Number(k)" />
      </el-select>
      <el-button type="primary" :icon="Search" @click="loadData">查询</el-button>
      <el-button :icon="Refresh" @click="resetQuery">重置</el-button>
    </div>

    <el-table v-loading="loading" :data="list" border stripe>
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column prop="bank_title" label="目标题库" show-overflow-tooltip />
      <el-table-column label="进度" width="160">
        <template #default="{ row }">
          <span v-if="row.total_count">{{ row.parsed_count || 0 }} / {{ row.total_count }}</span>
          <span v-else>—</span>
        </template>
      </el-table-column>
      <el-table-column label="状态" width="110">
        <template #default="{ row }">
          <el-tag :type="IMPORT_TASK_STATUS_MAP[row.status]?.type || 'info'">
            {{ IMPORT_TASK_STATUS_MAP[row.status]?.label || '未知' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="创建时间" width="180" />
      <el-table-column prop="updated_at" label="更新时间" width="180" />
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
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { Search, Refresh } from '@element-plus/icons-vue'
import { fetchImportTasks } from '@/api/import'
import { IMPORT_TASK_STATUS_MAP, DEFAULT_PAGE_SIZE } from '@/constants'
import type { ImportTaskItem } from '@/types/api.d'

const loading = ref(false)
const list = ref<ImportTaskItem[]>([])
const total = ref(0)
const query = reactive({
  status: undefined as number | undefined,
  page: 1,
  page_size: DEFAULT_PAGE_SIZE,
})

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

function resetQuery() {
  query.status = undefined
  query.page = 1
  loadData()
}

onMounted(loadData)
</script>

<style scoped lang="scss">
.pager {
  margin-top: 16px;
  justify-content: flex-end;
}
</style>
