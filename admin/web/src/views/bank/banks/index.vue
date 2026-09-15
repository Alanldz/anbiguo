<template>
  <div class="page-container">
    <div class="filter-bar">
      <el-input v-model="query.keyword" placeholder="题库标题/手机号" clearable @keyup.enter="loadData" />
      <el-select v-model="query.status" placeholder="状态" clearable style="width: 160px" @change="loadData">
        <el-option v-for="(v, k) in BANK_STATUS_MAP" :key="k" :label="v.label" :value="Number(k)" />
      </el-select>
      <el-button type="primary" :icon="Search" @click="loadData">查询</el-button>
      <el-button :icon="Refresh" @click="resetQuery">重置</el-button>
    </div>

    <el-table v-loading="loading" :data="list" border stripe>
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column prop="title" label="题库标题" show-overflow-tooltip />
      <el-table-column prop="question_count" label="题目数" width="90" />
      <el-table-column prop="user_mobile" label="提交人" width="140" />
      <el-table-column label="状态" width="100">
        <template #default="{ row }">
          <el-tag :type="BANK_STATUS_MAP[row.status]?.type || 'info'">
            {{ BANK_STATUS_MAP[row.status]?.label || '未知' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="创建时间" width="180" />
      <el-table-column label="操作" width="220" fixed="right">
        <template #default="{ row }">
          <el-button
            v-if="row.status === BANK_AUDIT_PENDING"
            link
            type="primary"
            @click="openAudit(row)"
          >
            审核
          </el-button>
          <el-switch
            v-model="row._online"
            inline-prompt
            active-text="上架"
            inactive-text="下架"
            :disabled="row.status === BANK_AUDIT_PENDING || row.status === BANK_AUDIT_REJECT"
            @change="(val: boolean) => handleToggleOnline(row, val)"
          />
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

    <!-- 审核弹窗 -->
    <el-dialog v-model="auditVisible" title="题库审核" width="460px">
      <el-form label-width="80px">
        <el-form-item label="题库">{{ auditRow?.title }}</el-form-item>
        <el-form-item label="结果">
          <el-radio-group v-model="auditForm.status">
            <el-radio :value="BANK_AUDIT_PASS">通过</el-radio>
            <el-radio :value="BANK_AUDIT_REJECT">拒绝</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="auditForm.remark" type="textarea" :rows="3" placeholder="审核备注（拒绝时建议填写原因）" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="auditVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="handleAudit">提交</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Refresh } from '@element-plus/icons-vue'
import { fetchBankList, auditBank, updateBankStatus } from '@/api/bank'
import {
  BANK_STATUS_MAP,
  BANK_AUDIT_PASS,
  BANK_AUDIT_REJECT,
  BANK_AUDIT_PENDING,
  BANK_STATUS_ONLINE,
  BANK_STATUS_OFFLINE,
  DEFAULT_PAGE_SIZE,
} from '@/constants'
import type { BankItem } from '@/types/api.d'

const loading = ref(false)
const submitting = ref(false)
const list = ref<(BankItem & { _online?: boolean })[]>([])
const total = ref(0)
const query = reactive({
  keyword: '',
  status: undefined as number | undefined,
  page: 1,
  page_size: DEFAULT_PAGE_SIZE,
})

const auditVisible = ref(false)
const auditRow = ref<BankItem | null>(null)
const auditForm = reactive({ status: BANK_AUDIT_PASS, remark: '' })

function syncOnline() {
  list.value.forEach((b) => {
    b._online = b.status === BANK_STATUS_ONLINE
  })
}

async function loadData() {
  loading.value = true
  try {
    const res = await fetchBankList({ ...query })
    list.value = res.list
    total.value = res.pagination.total
    syncOnline()
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

function openAudit(row: BankItem) {
  auditRow.value = row
  auditForm.status = BANK_AUDIT_PASS
  auditForm.remark = ''
  auditVisible.value = true
}

async function handleAudit() {
  if (!auditRow.value) return
  submitting.value = true
  try {
    await auditBank(auditRow.value.id, { ...auditForm })
    ElMessage.success('审核完成')
    auditVisible.value = false
    loadData()
  } finally {
    submitting.value = false
  }
}

function handleToggleOnline(row: BankItem & { _online?: boolean }, val: boolean) {
  const status = val ? BANK_STATUS_ONLINE : BANK_STATUS_OFFLINE
  ElMessageBox.confirm(`确定${val ? '上架' : '下架'}题库「${row.title}」吗？`, '提示', { type: 'warning' })
    .then(async () => {
      try {
        await updateBankStatus(row.id, { status })
        row.status = status
        ElMessage.success('操作成功')
      } catch {
        row._online = !val // 回滚
      }
    })
    .catch(() => {
      row._online = !val // 回滚
    })
}

onMounted(loadData)
</script>

<style scoped lang="scss">
.pager {
  margin-top: 16px;
  justify-content: flex-end;
}
</style>
