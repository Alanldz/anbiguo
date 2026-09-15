<template>
  <div class="page-container">
    <div class="res-layout">
      <!-- 左侧分类树 -->
      <el-card class="res-cats" shadow="never">
        <template #header>
          <div class="res-cats__head">
            <span>资料分类</span>
            <el-button type="primary" link :icon="Plus" @click="openCatCreate(null)">新增</el-button>
          </div>
        </template>
        <el-tree
          v-loading="catLoading"
          :data="catTree"
          :props="{ label: 'name', children: 'children' }"
          node-key="id"
          :expand-on-click-node="false"
          class="res-cats__tree"
          @node-click="onCatClick"
        >
          <template #default="{ data }">
            <div class="res-cats__node">
              <span :class="{ 'is-active': query.category_id === data.id }">{{ data.name }}</span>
              <span class="res-cats__ops">
                <el-icon title="新增子分类" @click.stop="openCatCreate(data.id)"><Plus /></el-icon>
                <el-icon title="编辑" @click.stop="openCatEdit(data)"><Edit /></el-icon>
                <el-icon title="删除" @click.stop="handleCatDelete(data)"><Delete /></el-icon>
              </span>
            </div>
          </template>
        </el-tree>
        <el-empty v-if="!catLoading && catTree.length === 0" description="暂无分类" :image-size="50" />
      </el-card>

      <!-- 右侧资料列表 -->
      <el-card class="res-main" shadow="never">
        <div class="filter-bar">
          <el-input v-model="query.keyword" placeholder="文件名" clearable style="width: 200px" @keyup.enter="loadData" />
          <el-select v-model="query.biz_type" placeholder="业务类型" clearable style="width: 150px" @change="loadData">
            <el-option v-for="(v, k) in FILE_BIZ_MAP" :key="k" :label="v" :value="Number(k)" />
          </el-select>
          <el-button type="primary" :icon="Search" @click="loadData">查询</el-button>
          <el-button :icon="Refresh" @click="resetQuery">重置</el-button>
          <el-button type="success" :icon="Upload" @click="openRegister">上传登记</el-button>
        </div>

        <el-table v-loading="loading" :data="list" border stripe>
          <el-table-column prop="origin_name" label="文件名" min-width="180" show-overflow-tooltip />
          <el-table-column prop="category_name" label="分类" width="120" />
          <el-table-column label="类型" width="100">
            <template #default="{ row }">{{ row.biz_type_text }}</template>
          </el-table-column>
          <el-table-column prop="file_size_text" label="大小" width="110" />
          <el-table-column label="状态" width="90">
            <template #default="{ row }">
              <el-tag :type="FILE_STATUS_MAP[row.status]?.type || 'info'">{{ FILE_STATUS_MAP[row.status]?.label }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="created_at" label="上传时间" width="170" />
          <el-table-column label="操作" width="150" fixed="right">
            <template #default="{ row }">
              <el-button link type="primary" @click="handleDownload(row)">下载</el-button>
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
      </el-card>
    </div>

    <!-- 分类编辑弹窗 -->
    <el-dialog v-model="catVisible" :title="catIsEdit ? '编辑分类' : '新增分类'" width="420px">
      <el-form label-width="70px">
        <el-form-item label="名称" required>
          <el-input v-model="catForm.name" placeholder="请输入分类名称" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="catForm.sort_order" :min="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="catVisible = false">取消</el-button>
        <el-button type="primary" :loading="catSubmitting" @click="handleCatSubmit">确定</el-button>
      </template>
    </el-dialog>

    <!-- 上传登记弹窗 -->
    <el-dialog v-model="regVisible" title="上传登记" width="480px">
      <el-form ref="regRef" :model="regForm" :rules="regRules" label-width="80px">
        <el-form-item label="选择文件">
          <el-upload
            class="res-upload"
            drag
            :auto-upload="false"
            :limit="1"
            :on-change="onFileChange"
            :on-remove="onFileRemove"
          >
            <el-icon class="el-icon--upload"><UploadFilled /></el-icon>
            <div class="el-upload__text">拖入或<em>点击选择</em>文件</div>
          </el-upload>
        </el-form-item>
        <el-form-item label="文件名" prop="origin_name">
          <el-input v-model="regForm.origin_name" placeholder="文件名称" />
        </el-form-item>
        <el-form-item label="分类">
          <el-select v-model="regForm.category_id" placeholder="选择分类（选填）" clearable style="width: 100%">
            <el-option v-for="c in flatCats" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="业务类型">
          <el-select v-model="regForm.biz_type" style="width: 100%">
            <el-option v-for="(v, k) in FILE_BIZ_MAP" :key="k" :label="v" :value="Number(k)" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="regVisible = false">取消</el-button>
        <el-button type="primary" :loading="regSubmitting" @click="handleRegister">登记</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules, type UploadFile } from 'element-plus'
import { Search, Refresh, Plus, Delete, Edit, Upload, UploadFilled } from '@element-plus/icons-vue'
import {
  fetchFileCategories,
  createFileCategory,
  updateFileCategory,
  deleteFileCategory,
  fetchFileAssets,
  registerFileAsset,
  deleteFileAsset,
  fetchFileDownloadUrl,
} from '@/api/file'
import {
  FILE_BIZ_MAP,
  FILE_STATUS_MAP,
  DEFAULT_PAGE_SIZE,
  PAGE_SIZE_OPTIONS,
} from '@/constants'
import type { FileCategoryItem, FileAssetItem } from '@/types/api.d'

const catLoading = ref(false)
const catTree = ref<FileCategoryItem[]>([])
const flatCats = ref<FileCategoryItem[]>([])
const loading = ref(false)
const list = ref<FileAssetItem[]>([])
const total = ref(0)

const query = reactive({
  keyword: '',
  biz_type: undefined as number | undefined,
  category_id: undefined as number | undefined,
  page: 1,
  page_size: DEFAULT_PAGE_SIZE,
})

// 后端返回扁平数组，这里组装成树
function buildTree(items: FileCategoryItem[]): FileCategoryItem[] {
  const map = new Map<number, FileCategoryItem>()
  items.forEach((i) => map.set(i.id, { ...i, children: [] }))
  const root: FileCategoryItem[] = []
  map.forEach((node) => {
    if (node.parent_id && map.has(node.parent_id)) {
      map.get(node.parent_id)!.children!.push(node)
    } else {
      root.push(node)
    }
  })
  return root
}

function flatten(items: FileCategoryItem[]): FileCategoryItem[] {
  const out: FileCategoryItem[] = []
  items.forEach((i) => {
    out.push(i)
    if (i.children?.length) out.push(...flatten(i.children))
  })
  return out
}

// 分类弹窗
const catVisible = ref(false)
const catIsEdit = ref(false)
const catEditId = ref<number | null>(null)
const catSubmitting = ref(false)
const catForm = reactive({ name: '', parent_id: undefined as number | undefined, sort_order: 0 })

function openCatCreate(parentId: number | null) {
  catIsEdit.value = false
  catEditId.value = null
  catForm.name = ''
  catForm.parent_id = parentId ?? undefined
  catForm.sort_order = 0
  catVisible.value = true
}

function openCatEdit(data: FileCategoryItem) {
  catIsEdit.value = true
  catEditId.value = data.id
  catForm.name = data.name
  catForm.parent_id = data.parent_id
  catForm.sort_order = data.sort_order
  catVisible.value = true
}

async function handleCatSubmit() {
  if (!catForm.name.trim()) {
    ElMessage.warning('请输入分类名称')
    return
  }
  catSubmitting.value = true
  try {
    if (catIsEdit.value && catEditId.value) {
      await updateFileCategory(catEditId.value, { ...catForm })
      ElMessage.success('更新成功')
    } else {
      await createFileCategory({ ...catForm })
      ElMessage.success('新增成功')
    }
    catVisible.value = false
    await loadCategories()
  } finally {
    catSubmitting.value = false
  }
}

function handleCatDelete(data: FileCategoryItem) {
  ElMessageBox.confirm(`确定删除分类「${data.name}」吗？`, '提示', { type: 'warning' })
    .then(async () => {
      await deleteFileCategory(data.id)
      ElMessage.success('删除成功')
      await loadCategories()
    })
    .catch(() => {})
}

function onCatClick(data: FileCategoryItem) {
  query.category_id = query.category_id === data.id ? undefined : data.id
  query.page = 1
  loadData()
}

// 上传登记
const regVisible = ref(false)
const regRef = ref<FormInstance>()
const regSubmitting = ref(false)
const regForm = reactive({
  origin_name: '',
  file_ext: '',
  object_key: '',
  file_size: undefined as number | undefined,
  category_id: undefined as number | undefined,
  biz_type: 3,
})
const regRules: FormRules = {
  origin_name: [{ required: true, message: '请输入文件名称', trigger: 'blur' }],
}

function onFileChange(file: UploadFile) {
  regForm.origin_name = file.name
  regForm.file_ext = (file.name.split('.').pop() || '').toLowerCase()
  regForm.file_size = file.size
  // TODO 七牛直传未接入：先取元信息并生成临时 object_key 走「上传后登记」接口
  // 正式流程：fetchUploadToken 取凭证 -> 前端直传七牛 -> 用返回 object_key 登记
  regForm.object_key = `temp_${Date.now()}_${file.name}`
}

function onFileRemove() {
  regForm.origin_name = ''
  regForm.file_ext = ''
  regForm.object_key = ''
  regForm.file_size = undefined
}

function openRegister() {
  regForm.origin_name = ''
  regForm.file_ext = ''
  regForm.object_key = ''
  regForm.file_size = undefined
  regForm.category_id = query.category_id
  regForm.biz_type = 3
  regVisible.value = true
}

async function handleRegister() {
  if (!regRef.value) return
  await regRef.value.validate(async (valid) => {
    if (!valid) return
    if (!regForm.object_key) {
      ElMessage.warning('请先选择文件')
      return
    }
    regSubmitting.value = true
    try {
      await registerFileAsset({
        object_key: regForm.object_key,
        origin_name: regForm.origin_name,
        file_ext: regForm.file_ext,
        file_size: regForm.file_size,
        category_id: regForm.category_id,
        biz_type: regForm.biz_type,
      })
      ElMessage.success('登记成功')
      regVisible.value = false
      loadData()
    } finally {
      regSubmitting.value = false
    }
  })
}

async function handleDownload(row: FileAssetItem) {
  try {
    const res = await fetchFileDownloadUrl(row.id)
    if (res.url) window.open(res.url, '_blank')
  } catch {
    // 错误已由 request 拦截器统一提示
  }
}

function handleDelete(row: FileAssetItem) {
  ElMessageBox.confirm(`确定删除资料「${row.origin_name}」吗？`, '提示', { type: 'warning' })
    .then(async () => {
      await deleteFileAsset(row.id)
      ElMessage.success('删除成功')
      loadData()
    })
    .catch(() => {})
}

async function loadCategories() {
  catLoading.value = true
  try {
    const items = await fetchFileCategories()
    catTree.value = buildTree(items)
    flatCats.value = flatten(items)
  } finally {
    catLoading.value = false
  }
}

async function loadData() {
  loading.value = true
  try {
    const res = await fetchFileAssets({ ...query })
    list.value = res.list
    total.value = res.pagination.total
  } finally {
    loading.value = false
  }
}

function resetQuery() {
  query.keyword = ''
  query.biz_type = undefined
  query.category_id = undefined
  query.page = 1
  loadData()
}

onMounted(async () => {
  await loadCategories()
  loadData()
})
</script>

<style scoped lang="scss">
.res-layout {
  display: flex;
  gap: 16px;
  align-items: flex-start;
}

.res-cats {
  width: 240px;
  flex-shrink: 0;

  &__head {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  &__node {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;

    .is-active {
      color: var(--el-color-primary);
      font-weight: 600;
    }
  }

  &__ops {
    display: none;
    gap: 8px;

    .res-cats__node:hover & {
      display: inline-flex;
    }
  }
}

.res-main {
  flex: 1;
  min-width: 0;
}

.res-upload {
  width: 100%;
}
</style>
