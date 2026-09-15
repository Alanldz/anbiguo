<template>
  <div class="page-container">
    <div class="filter-bar">
      <el-select v-model="query.type" placeholder="类型" clearable style="width: 140px" @change="loadData">
        <el-option v-for="(v, k) in FEEDBACK_TYPE_MAP" :key="k" :label="v" :value="Number(k)" />
      </el-select>
      <el-select v-model="query.status" placeholder="状态" clearable style="width: 140px" @change="loadData">
        <el-option v-for="(v, k) in FEEDBACK_STATUS_MAP" :key="k" :label="v.label" :value="Number(k)" />
      </el-select>
      <el-input v-model="query.keyword" placeholder="内容/联系方式" clearable style="width: 220px" @keyup.enter="loadData" />
      <el-button type="primary" :icon="Search" @click="loadData">查询</el-button>
      <el-button :icon="Refresh" @click="resetQuery">重置</el-button>
    </div>

    <el-table v-loading="loading" :data="list" border stripe>
      <el-table-column prop="id" label="ID" width="70" />
      <el-table-column label="用户" width="160">
        <template #default="{ row }">{{ row.user?.nickname || '-' }} / {{ row.user?.phone || '-' }}</template>
      </el-table-column>
      <el-table-column label="类型" width="110">
        <template #default="{ row }">{{ FEEDBACK_TYPE_MAP[row.type] ?? row.type }}</template>
      </el-table-column>
      <el-table-column prop="content" label="内容" min-width="240" show-overflow-tooltip />
      <el-table-column prop="contact" label="联系方式" width="150" show-overflow-tooltip />
      <el-table-column label="状态" width="100">
        <template #default="{ row }">
          <el-tag :type="FEEDBACK_STATUS_MAP[row.status]?.type || 'info'">
            {{ FEEDBACK_STATUS_MAP[row.status]?.label || '未知' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="提交时间" width="180" />
      <el-table-column label="操作" width="100" fixed="right">
        <template #default="{ row }">
          <el-button link type="primary" :icon="View" @click="openDetail(row)">查看</el-button>
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

    <!-- 详情 / 处理抽屉 -->
    <el-drawer v-model="drawerVisible" title="反馈详情" size="460px">
      <template v-if="cur">
        <el-descriptions :column="1" border>
          <el-descriptions-item label="用户">{{ cur.user?.nickname || '-' }}（{{ cur.user?.phone || '-' }}）</el-descriptions-item>
          <el-descriptions-item label="类型">{{ FEEDBACK_TYPE_MAP[cur.type] ?? cur.type }}</el-descriptions-item>
          <el-descriptions-item label="联系方式">{{ cur.contact || '-' }}</el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-tag :type="FEEDBACK_STATUS_MAP[cur.status]?.type || 'info'">
              {{ FEEDBACK_STATUS_MAP[cur.status]?.label || '未知' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="提交时间">{{ cur.created_at }}</el-descriptions-item>
        </el-descriptions>

        <div class="block-title">反馈内容</div>
        <div class="content-box">{{ cur.content }}</div>

        <div v-if="cur.images && cur.images.length" class="block-title">图片（点击预览）</div>
        <div v-if="cur.images && cur.images.length" class="img-list">
          <el-image
            v-for="(img, i) in cur.images"
            :key="i"
            :src="img"
            :preview-src-list="cur.images"
            :initial-index="i"
            fit="cover"
            class="img-item"
          />
        </div>

        <el-divider />

        <el-form label-width="80px">
          <el-form-item label="处理结果">
            <el-radio-group v-model="handleForm.status">
              <el-radio :value="FEEDBACK_STATUS_HANDLED">已处理</el-radio>
              <el-radio :value="FEEDBACK_STATUS_IGNORED">已忽略</el-radio>
            </el-radio-group>
          </el-form-item>
          <el-form-item label="回复">
            <el-input v-model="handleForm.reply" type="textarea" :rows="3" placeholder="处理回复（可空）" />
          </el-form-item>
        </el-form>
        <div class="drawer-footer">
          <el-button @click="drawerVisible = false">关闭</el-button>
          <el-button type="primary" :loading="submitting" @click="submitHandle">提交处理</el-button>
        </div>
      </template>
    </el-drawer>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Search, Refresh, View } from '@element-plus/icons-vue'
import { fetchFeedbacks, handleFeedback } from '@/api/feedback'
import {
  FEEDBACK_TYPE_MAP,
  FEEDBACK_STATUS_MAP,
  FEEDBACK_STATUS_HANDLED,
  FEEDBACK_STATUS_IGNORED,
  DEFAULT_PAGE_SIZE,
} from '@/constants'
import type { FeedbackItem } from '@/types/api.d'

const loading = ref(false)
const submitting = ref(false)
const list = ref<FeedbackItem[]>([])
const total = ref(0)
const query = reactive({
  type: undefined as number | undefined,
  status: undefined as number | undefined,
  keyword: '',
  page: 1,
  page_size: DEFAULT_PAGE_SIZE,
})

const drawerVisible = ref(false)
const cur = ref<FeedbackItem | null>(null)
const handleForm = reactive({ status: FEEDBACK_STATUS_HANDLED, reply: '' })

async function loadData() {
  loading.value = true
  try {
    const res = await fetchFeedbacks({ ...query })
    list.value = res.list
    total.value = res.pagination.total
  } finally {
    loading.value = false
  }
}

function resetQuery() {
  query.type = undefined
  query.status = undefined
  query.keyword = ''
  query.page = 1
  loadData()
}

function openDetail(row: FeedbackItem) {
  cur.value = row
  handleForm.status =
    row.status === FEEDBACK_STATUS_IGNORED ? FEEDBACK_STATUS_IGNORED : FEEDBACK_STATUS_HANDLED
  handleForm.reply = row.reply || ''
  drawerVisible.value = true
}

async function submitHandle() {
  if (!cur.value) return
  submitting.value = true
  try {
    await handleFeedback(cur.value.id, {
      status: handleForm.status as 1 | 2,
      reply: handleForm.reply.trim(),
    })
    ElMessage.success('处理成功')
    drawerVisible.value = false
    loadData()
  } finally {
    submitting.value = false
  }
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
.block-title {
  font-weight: 600;
  margin: 16px 0 8px;
  color: #303133;
}
.content-box {
  white-space: pre-wrap;
  word-break: break-all;
  background: #f5f7fa;
  border-radius: 4px;
  padding: 10px 12px;
  line-height: 1.6;
}
.img-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.img-item {
  width: 90px;
  height: 90px;
  border-radius: 4px;
}
.drawer-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 16px;
}
</style>
