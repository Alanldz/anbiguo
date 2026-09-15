<template>
  <div class="page-container">
    <div class="filter-bar">
      <el-select v-model="query.position" placeholder="位置" clearable style="width: 160px" @change="loadData">
        <el-option label="首页轮播" value="home_top" />
        <el-option label="首页推荐" value="home_recommend" />
        <el-option label="我的页入口" value="mine_entry" />
      </el-select>
      <el-button type="primary" :icon="Search" @click="loadData">查询</el-button>
      <el-button :icon="Refresh" @click="resetQuery">重置</el-button>
      <el-button type="success" :icon="Plus" @click="openCreate">新建轮播</el-button>
    </div>

    <el-table v-loading="loading" :data="list" border stripe>
      <el-table-column prop="id" label="ID" width="70" />
      <el-table-column prop="title" label="标题" />
      <el-table-column label="图片" width="120">
        <template #default="{ row }">
          <el-image :src="row.image" fit="cover" style="width: 80px; height: 44px" />
        </template>
      </el-table-column>
      <el-table-column label="位置" width="100">
        <template #default="{ row }">{{ BANNER_POSITION_MAP[row.position] ?? row.position }}</template>
      </el-table-column>
      <el-table-column prop="sort" label="排序" width="80" />
      <el-table-column label="状态" width="90">
        <template #default="{ row }">
          <el-tag :type="BANNER_STATUS_MAP[row.status]?.type || 'info'">
            {{ BANNER_STATUS_MAP[row.status]?.label || '未知' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="start_at" label="开始时间" width="170" />
      <el-table-column prop="end_at" label="结束时间" width="170" />
      <el-table-column label="操作" width="160" fixed="right">
        <template #default="{ row }">
          <el-button link type="primary" :icon="Edit" @click="openEdit(row)">编辑</el-button>
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

    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="520px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="90px">
        <el-form-item label="标题" prop="title">
          <el-input v-model="form.title" />
        </el-form-item>
        <el-form-item label="图片" prop="image">
          <el-input v-model="form.image" placeholder="图片 URL" />
        </el-form-item>
        <el-form-item label="位置" prop="position">
          <el-select v-model="form.position" style="width: 100%">
            <el-option label="首页轮播" value="home_top" />
            <el-option label="首页推荐" value="home_recommend" />
            <el-option label="我的页入口" value="mine_entry" />
          </el-select>
        </el-form-item>
        <el-form-item label="跳转类型" prop="link_type">
          <el-select v-model="form.link_type" style="width: 100%">
            <el-option label="不跳转" :value="1" />
            <el-option label="题库" :value="2" />
            <el-option label="学习资料" :value="3" />
            <el-option label="外链" :value="4" />
            <el-option label="活动页" :value="5" />
          </el-select>
        </el-form-item>
        <el-form-item label="跳转值">
          <el-input v-model="form.link_value" placeholder="link_value" />
        </el-form-item>
        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="form.sort" :min="0" />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="form.status">
            <el-radio :value="1">启用</el-radio>
            <el-radio :value="2">停用</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="生效时间">
          <el-date-picker
            v-model="timeRange"
            type="datetimerange"
            range-separator="至"
            start-placeholder="开始"
            end-placeholder="结束"
            value-format="YYYY-MM-DD HH:mm:ss"
            style="width: 100%"
          />
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
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { Search, Refresh, Plus, Edit, Delete } from '@element-plus/icons-vue'
import { fetchBannerList, createBanner, updateBanner, deleteBanner } from '@/api/banner'
import { BANNER_STATUS_MAP, BANNER_POSITION_MAP, DEFAULT_PAGE_SIZE } from '@/constants'
import type { BannerItem, BannerPayload } from '@/types/api.d'

const loading = ref(false)
const submitting = ref(false)
const list = ref<BannerItem[]>([])
const total = ref(0)
const query = reactive({
  position: undefined as string | undefined,
  page: 1,
  page_size: DEFAULT_PAGE_SIZE,
})

const dialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref<number | null>(null)
const dialogTitle = ref('')
const formRef = ref<FormInstance>()
const timeRange = ref<[string, string]>(['', ''])

const emptyForm = (): BannerPayload => ({
  title: '',
  image: '',
  position: 'home_top',
  link_type: 1,
  link_value: '',
  sort: 0,
  status: 1,
  start_at: '',
  end_at: '',
})
const form = reactive(emptyForm())

const rules: FormRules = {
  title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
  image: [{ required: true, message: '请输入图片地址', trigger: 'blur' }],
  position: [{ required: true, message: '请选择位置', trigger: 'change' }],
  status: [{ required: true, message: '请选择状态', trigger: 'change' }],
}

async function loadData() {
  loading.value = true
  try {
    const res = await fetchBannerList({ ...query })
    list.value = res.list
    total.value = res.pagination.total
  } finally {
    loading.value = false
  }
}

function resetQuery() {
  query.position = undefined
  query.page = 1
  loadData()
}

function openCreate() {
  isEdit.value = false
  editId.value = null
  dialogTitle.value = '新建轮播'
  Object.assign(form, emptyForm())
  timeRange.value = ['', '']
  dialogVisible.value = true
}

function openEdit(row: BannerItem) {
  isEdit.value = true
  editId.value = row.id
  dialogTitle.value = '编辑轮播'
  Object.assign(form, { ...row })
  timeRange.value = [row.start_at, row.end_at]
  dialogVisible.value = true
}

async function handleSubmit() {
  if (!formRef.value) return
  await formRef.value.validate(async (valid) => {
    if (!valid) return
    form.start_at = timeRange.value?.[0] || ''
    form.end_at = timeRange.value?.[1] || ''
    submitting.value = true
    try {
      if (isEdit.value && editId.value !== null) {
        await updateBanner(editId.value, { ...form })
        ElMessage.success('编辑成功')
      } else {
        await createBanner({ ...form })
        ElMessage.success('创建成功')
      }
      dialogVisible.value = false
      loadData()
    } finally {
      submitting.value = false
    }
  })
}

function handleDelete(row: BannerItem) {
  ElMessageBox.confirm(`确定删除轮播「${row.title}」吗？`, '提示', { type: 'warning' })
    .then(async () => {
      await deleteBanner(row.id)
      ElMessage.success('已删除')
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
