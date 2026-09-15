<template>
  <div class="page-container">
    <div class="filter-bar">
      <el-input v-model="query.keyword" placeholder="手机号/昵称" clearable @keyup.enter="loadData" />
      <el-select v-model="query.status" placeholder="状态" clearable style="width: 160px" @change="loadData">
        <el-option v-for="(v, k) in USER_STATUS_MAP" :key="k" :label="v.label" :value="Number(k)" />
      </el-select>
      <el-button type="primary" :icon="Search" @click="loadData">查询</el-button>
      <el-button :icon="Refresh" @click="resetQuery">重置</el-button>
    </div>

    <el-table v-loading="loading" :data="list" border stripe>
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column prop="mobile" label="手机号" width="150" />
      <el-table-column prop="nickname" label="昵称" />
      <el-table-column label="会员等级" width="120">
        <template #default="{ row }">{{ MEMBER_LEVEL_MAP[row.member_level] || '普通用户' }}</template>
      </el-table-column>
      <el-table-column label="状态" width="100">
        <template #default="{ row }">
          <el-tag :type="USER_STATUS_MAP[row.status]?.type || 'info'">
            {{ USER_STATUS_MAP[row.status]?.label || '未知' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="注册时间" width="180" />
      <el-table-column label="操作" width="120" fixed="right">
        <template #default="{ row }">
          <el-button
            v-if="row.status === USER_STATUS_NORMAL"
            link
            type="danger"
            @click="handleToggle(row, USER_STATUS_DISABLED)"
          >
            禁用
          </el-button>
          <el-button v-else link type="success" @click="handleToggle(row, USER_STATUS_NORMAL)">
            启用
          </el-button>
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
import { Search, Refresh } from '@element-plus/icons-vue'
import { fetchUserList, updateUserStatus } from '@/api/user'
import {
  USER_STATUS_MAP,
  USER_STATUS_NORMAL,
  USER_STATUS_DISABLED,
  MEMBER_LEVEL_MAP,
  DEFAULT_PAGE_SIZE,
} from '@/constants'
import type { UserItem } from '@/types/api.d'

const loading = ref(false)
const list = ref<UserItem[]>([])
const total = ref(0)
const query = reactive({
  keyword: '',
  status: undefined as number | undefined,
  page: 1,
  page_size: DEFAULT_PAGE_SIZE,
})

async function loadData() {
  loading.value = true
  try {
    const res = await fetchUserList({ ...query })
    list.value = res.list
    total.value = res.pagination.total
  } finally {
    loading.value = false
  }
}

function resetQuery() {
  query.keyword = ''
  query.status = undefined
  query.page = 1
  loadData()
}

function handleToggle(row: UserItem, status: number) {
  const action = status === USER_STATUS_DISABLED ? '禁用' : '启用'
  ElMessageBox.confirm(`确定${action}用户「${row.nickname || row.mobile}」吗？`, '提示', {
    type: 'warning',
  })
    .then(async () => {
      await updateUserStatus(row.id, { status })
      ElMessage.success(`${action}成功`)
      loadData()
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
