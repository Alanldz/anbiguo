<template>
  <div class="page-container">
    <el-tabs v-model="activeGroup" @tab-change="loadData">
      <el-tab-pane v-for="g in groups" :key="g.code" :label="g.name" :name="g.code" />
    </el-tabs>

    <el-table v-loading="loading" :data="list" border stripe>
      <el-table-column prop="config_key" label="配置键" width="240" />
      <el-table-column prop="description" label="说明" />
      <el-table-column label="配置值" min-width="220">
        <template #default="{ row }">
          <span v-if="row.is_secret === 1 && !row._editing" class="config-secret">
            {{ row.value }} <el-tag size="small" type="warning">密文</el-tag>
          </span>
          <span v-else-if="!row._editing">{{ row.value }}</span>
          <el-input
            v-else
            v-model="row._draft"
            :type="row.is_secret === 1 ? 'password' : 'text'"
            :placeholder="row.is_secret === 1 ? '输入新值以覆盖' : '输入配置值'"
            show-password
          />
        </template>
      </el-table-column>
      <el-table-column prop="value_type" label="类型" width="100" />
      <el-table-column prop="updated_at" label="更新时间" width="180" />
      <el-table-column label="操作" width="160" fixed="right">
        <template #default="{ row }">
          <template v-if="!row._editing">
            <el-button link type="primary" :icon="Edit" @click="startEdit(row)">编辑</el-button>
            <el-button link type="warning" :icon="Connection" :loading="row._testing" @click="handleTest(row)">测试连通</el-button>
          </template>
          <template v-else>
            <el-button link type="success" @click="handleSave(row)">保存</el-button>
            <el-button link type="info" @click="cancelEdit(row)">取消</el-button>
          </template>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Edit, Connection } from '@element-plus/icons-vue'
import { fetchConfigList, updateConfig, testConfig } from '@/api/config'
import type { ConfigItem, ConfigGroup } from '@/types/api.d'

const loading = ref(false)
const groups = ref<ConfigGroup[]>([])
const activeGroup = ref('')
const list = ref<ConfigItem[]>([])

type EditableConfig = ConfigItem & { _editing?: boolean; _draft?: string; _testing?: boolean }

async function loadData() {
  if (!activeGroup.value) return
  loading.value = true
  try {
    const res = await fetchConfigList(activeGroup.value)
    groups.value = res.groups
    list.value = res.list.map((item) => ({ ...item, _editing: false, _draft: '' }))
  } finally {
    loading.value = false
  }
}

function startEdit(row: EditableConfig) {
  row._editing = true
  // 密文项不回填掩码，留空表示覆盖
  row._draft = row.is_secret === 1 ? '' : row.value
}

function cancelEdit(row: EditableConfig) {
  row._editing = false
  row._draft = ''
}

async function handleSave(row: EditableConfig) {
  if (row.is_secret === 1 && !row._draft) {
    // 密文项留空表示不修改
    row._editing = false
    return
  }
  try {
    await updateConfig(row.id, { value: row._draft || '' })
    row.value = row.is_secret === 1 ? '••••••••' : row._draft || row.value
    row._editing = false
    ElMessage.success('保存成功')
  } catch {
    // 错误已统一提示
  }
}

/** API-ADM-101 配置连通性测试 */
async function handleTest(row: EditableConfig) {
  row._testing = true
  try {
    const res = await testConfig(row.id)
    ElMessage({
      type: res.ok ? 'success' : 'error',
      message: `${res.message}（耗时 ${res.latency_ms}ms）`,
    })
  } catch {
    // 错误已统一提示
  } finally {
    row._testing = false
  }
}

onMounted(async () => {
  // 先加载全部分组（不传 group 时后端可能返回默认分组，这里首屏取第一个分组）
  try {
    const res = await fetchConfigList()
    groups.value = res.groups
    if (groups.value.length) {
      activeGroup.value = groups.value[0].code
    }
  } catch {
    // 错误已统一提示
  }
  await loadData()
})
</script>

<style scoped lang="scss">
.config-secret {
  font-family: monospace;
  color: #6b7280;
}
</style>
