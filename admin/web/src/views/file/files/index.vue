<template>
  <div class="page-container">
    <div class="filter-bar">
      <el-input v-model="query.keyword" placeholder="文件名/object_key" clearable style="width: 220px" @keyup.enter="loadData" />
      <el-select v-model="query.biz_type" placeholder="业务类型" clearable style="width: 160px" @change="loadData">
        <el-option v-for="(v, k) in BIZ_TYPE_MAP" :key="k" :label="v" :value="Number(k)" />
      </el-select>
      <el-input v-model="query.storage" placeholder="存储方" clearable style="width: 160px" @keyup.enter="loadData" />
      <el-input v-model="query.user_id" placeholder="用户ID" clearable style="width: 140px" @keyup.enter="loadData" />
      <el-button type="primary" :icon="Search" @click="loadData">查询</el-button>
      <el-button :icon="Refresh" @click="resetQuery">重置</el-button>
    </div>

    <el-table v-loading="loading" :data="list" border stripe>
      <el-table-column prop="id" label="ID" width="70" />
      <el-table-column prop="origin_name" label="文件名" min-width="200" show-overflow-tooltip />
      <el-table-column prop="file_ext" label="后缀" width="90" />
      <el-table-column label="大小" width="120">
        <template #default="{ row }">{{ formatSize(row.file_size) }}</template>
      </el-table-column>
      <el-table-column label="业务类型" width="130">
        <template #default="{ row }">{{ BIZ_TYPE_MAP[row.biz_type] ?? row.biz_type }}</template>
      </el-table-column>
      <el-table-column prop="user_id" label="归属用户" width="100" />
      <el-table-column prop="storage" label="存储方" width="120" />
      <el-table-column label="公开" width="80">
        <template #default="{ row }">
          <el-tag :type="row.is_public === 1 ? 'success' : 'info'">
            {{ row.is_public === 1 ? '公开' : '私有' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="上传时间" width="180" />
      <el-table-column label="操作" width="90" fixed="right">
        <template #default="{ row }">
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
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Refresh, Delete } from '@element-plus/icons-vue'
import { fetchFiles, deleteFile } from '@/api/file'
import { BIZ_TYPE_MAP, DEFAULT_PAGE_SIZE } from '@/constants'
import type { FileAssetItem } from '@/types/api.d'

/** 文件大小格式化：B / KB / MB */
function formatSize(bytes: number): string {
  if (!bytes || bytes <= 0) return '0 B'
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB'
  return (bytes / 1024 / 1024).toFixed(2) + ' MB'
}

const loading = ref(false)
const list = ref<FileAssetItem[]>([])
const total = ref(0)
const query = reactive({
  keyword: '',
  biz_type: undefined as number | undefined,
  storage: '',
  user_id: undefined as number | undefined,
  page: 1,
  page_size: DEFAULT_PAGE_SIZE,
})

async function loadData() {
  loading.value = true
  try {
    const res = await fetchFiles({
      keyword: query.keyword || undefined,
      biz_type: query.biz_type,
      storage: query.storage || undefined,
      user_id: query.user_id,
      page: query.page,
      page_size: query.page_size,
    })
    list.value = res.list
    total.value = res.pagination.total
  } finally {
    loading.value = false
  }
}

function resetQuery() {
  query.keyword = ''
  query.biz_type = undefined
  query.storage = ''
  query.user_id = undefined
  query.page = 1
  loadData()
}

function handleDelete(row: FileAssetItem) {
  ElMessageBox.confirm(`确定删除文件「${row.origin_name}」吗？删除后为软删。`, '提示', { type: 'warning' })
    .then(async () => {
      try {
        await deleteFile(row.id)
        ElMessage.success('已删除')
        loadData()
      } catch {
        // 错误已由拦截器提示
      }
    })
    .catch(() => {})
}

onMounted(loadData)
</script>

<style scoped lang="scss">
.filter-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 16px;
  align-items: center;
}
.pager {
  margin-top: 16px;
  justify-content: flex-end;
}
</style>
