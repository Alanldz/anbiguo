<template>
  <div class="page-container">
    <el-tabs v-model="activeTab">
      <!-- Tab1 我的订单 -->
      <el-tab-pane label="我的订单" name="orders">
        <div class="filter-bar">
          <el-input v-model="orderQuery.order_no" placeholder="订单号" clearable style="width: 200px" @keyup.enter="loadOrders" />
          <el-input v-model="orderQuery.keyword" placeholder="用户昵称/标题" clearable style="width: 200px" @keyup.enter="loadOrders" />
          <el-select v-model="orderQuery.order_type" placeholder="订单类型" clearable style="width: 140px" @change="loadOrders">
            <el-option v-for="(v, k) in ORDER_TYPE_MAP" :key="k" :label="v" :value="Number(k)" />
          </el-select>
          <el-select v-model="orderQuery.status" placeholder="状态" clearable style="width: 140px" @change="loadOrders">
            <el-option v-for="(v, k) in ORDER_STATUS_MAP" :key="k" :label="v.label" :value="Number(k)" />
          </el-select>
          <el-button type="primary" :icon="Search" @click="loadOrders">查询</el-button>
          <el-button :icon="Refresh" @click="resetOrderQuery">重置</el-button>
        </div>

        <el-table v-loading="orderLoading" :data="orderList" border stripe>
          <el-table-column prop="id" label="ID" width="70" />
          <el-table-column prop="order_no" label="订单号" width="200" />
          <el-table-column label="用户" width="160">
            <template #default="{ row }">{{ row.user?.nickname || '-' }} / {{ row.user?.phone || '-' }}</template>
          </el-table-column>
          <el-table-column label="类型" width="110">
            <template #default="{ row }">{{ ORDER_TYPE_MAP[row.order_type] ?? row.order_type }}</template>
          </el-table-column>
          <el-table-column prop="biz_title" label="商品" show-overflow-tooltip />
          <el-table-column label="金额" width="220">
            <template #default="{ row }">
              <div>应付：{{ formatMoney(row.pay_amount) }}</div>
              <div class="sub">原价 {{ formatMoney(row.origin_amount) }} / 优惠 {{ formatMoney(row.discount_amount) }}</div>
            </template>
          </el-table-column>
          <el-table-column label="渠道" width="100">
            <template #default="{ row }">{{ PAY_CHANNEL_MAP[row.pay_channel] ?? row.pay_channel }}</template>
          </el-table-column>
          <el-table-column label="状态" width="100">
            <template #default="{ row }">
              <el-tag :type="ORDER_STATUS_MAP[row.status]?.type || 'info'">
                {{ ORDER_STATUS_MAP[row.status]?.label || '未知' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="created_at" label="创建时间" width="170" />
          <el-table-column label="操作" width="100" fixed="right">
            <template #default="{ row }">
              <el-button
                v-if="row.status === ORDER_STATUS_PAID"
                link
                type="danger"
                :icon="RefreshLeft"
                @click="handleRefund(row)"
              >
                退款
              </el-button>
              <span v-else>-</span>
            </template>
          </el-table-column>
        </el-table>

        <el-pagination
          v-model:current-page="orderQuery.page"
          v-model:page-size="orderQuery.page_size"
          :total="orderTotal"
          :page-sizes="[10, 20, 50]"
          layout="total, sizes, prev, pager, next"
          class="pager"
          @current-change="loadOrders"
          @size-change="loadOrders"
        />
      </el-tab-pane>

      <!-- Tab2 会员套餐 -->
      <el-tab-pane label="会员套餐" name="plans">
        <div class="filter-bar">
          <el-button type="primary" :icon="Refresh" @click="loadPlans">刷新</el-button>
        </div>
        <el-table v-loading="planLoading" :data="planList" border stripe>
          <el-table-column prop="id" label="ID" width="70" />
          <el-table-column prop="name" label="名称" min-width="160" />
          <el-table-column label="等级" width="90">
            <template #default="{ row }">{{ PLAN_LEVEL_MAP[row.level] ?? row.level }}</template>
          </el-table-column>
          <el-table-column prop="duration_days" label="时长(天)" width="100" />
          <el-table-column label="价格" width="200">
            <template #default="{ row }">
              <div>{{ formatMoney(row.price_amount) }}</div>
              <div class="sub">原价 {{ formatMoney(row.origin_amount) }}</div>
            </template>
          </el-table-column>
          <el-table-column prop="ai_import_quota" label="AI导入额度" width="110" />
          <el-table-column label="推荐" width="80">
            <template #default="{ row }">
              <el-tag v-if="row.is_recommend === 1" type="success">推荐</el-tag>
              <span v-else>-</span>
            </template>
          </el-table-column>
          <el-table-column label="状态" width="90">
            <template #default="{ row }">
              <el-tag :type="PLAN_STATUS_MAP[row.status]?.type || 'info'">
                {{ PLAN_STATUS_MAP[row.status]?.label || '未知' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="权益" min-width="200" show-overflow-tooltip>
            <template #default="{ row }">{{ (row.benefits || []).join(' / ') || '-' }}</template>
          </el-table-column>
          <el-table-column label="操作" width="90" fixed="right">
            <template #default="{ row }">
              <el-button link type="primary" :icon="Edit" @click="openPlanEdit(row)">编辑</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-tab-pane>
    </el-tabs>

    <!-- 会员套餐编辑弹窗 -->
    <el-dialog v-model="planDialog" title="编辑会员套餐" width="560px">
      <el-form ref="planFormRef" :model="planForm" label-width="100px">
        <el-form-item label="名称">
          <el-input v-model="planForm.name" />
        </el-form-item>
        <el-form-item label="等级">
          <el-select v-model="planForm.level" style="width: 100%">
            <el-option v-for="(v, k) in PLAN_LEVEL_MAP" :key="k" :label="v" :value="Number(k)" />
          </el-select>
        </el-form-item>
        <el-form-item label="时长(天)">
          <el-input-number v-model="planForm.duration_days" :min="1" />
        </el-form-item>
        <el-form-item label="现价">
          <el-input-number v-model="planForm.price_amount" :min="0" :precision="2" :step="1" />
        </el-form-item>
        <el-form-item label="原价">
          <el-input-number v-model="planForm.origin_amount" :min="0" :precision="2" :step="1" />
        </el-form-item>
        <el-form-item label="AI导入额度">
          <el-input-number v-model="planForm.ai_import_quota" :min="0" />
        </el-form-item>
        <el-form-item label="权益">
          <el-input
            v-model="planBenefitsText"
            type="textarea"
            :rows="4"
            placeholder="每行一项，保存时按行拆分为数组"
          />
        </el-form-item>
        <el-form-item label="描述">
          <el-input v-model="planForm.description" type="textarea" :rows="2" />
        </el-form-item>
        <el-form-item label="推荐">
          <el-switch v-model="planForm.is_recommend" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="planForm.sort_order" :min="0" />
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="planForm.status">
            <el-radio :value="PLAN_STATUS_ONLINE">上架</el-radio>
            <el-radio :value="PLAN_STATUS_OFFLINE">下架</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="planDialog = false">取消</el-button>
        <el-button type="primary" :loading="planSubmitting" @click="submitPlan">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance } from 'element-plus'
import { Search, Refresh, Edit, RefreshLeft } from '@element-plus/icons-vue'
import { fetchOrders, refundOrder } from '@/api/order'
import { fetchMemberPlans, updateMemberPlan } from '@/api/plan'
import {
  ORDER_TYPE_MAP,
  ORDER_STATUS_MAP,
  ORDER_STATUS_PAID,
  PAY_CHANNEL_MAP,
  PLAN_LEVEL_MAP,
  PLAN_STATUS_MAP,
  PLAN_STATUS_ONLINE,
  PLAN_STATUS_OFFLINE,
  DEFAULT_PAGE_SIZE,
} from '@/constants'
import type { OrderItem, MemberPlanItem } from '@/types/api.d'

/** 金额格式化（单位以后端返回为准，固定两位小数） */
function formatMoney(v: number): string {
  return '¥' + (Number(v) || 0).toFixed(2)
}

const activeTab = ref<'orders' | 'plans'>('orders')

/* ---------- 订单 Tab ---------- */
const orderLoading = ref(false)
const orderList = ref<OrderItem[]>([])
const orderTotal = ref(0)
const orderQuery = reactive({
  order_no: '',
  keyword: '',
  order_type: undefined as number | undefined,
  status: undefined as number | undefined,
  page: 1,
  page_size: DEFAULT_PAGE_SIZE,
})

async function loadOrders() {
  orderLoading.value = true
  try {
    const res = await fetchOrders({ ...orderQuery })
    orderList.value = res.list
    orderTotal.value = res.pagination.total
  } finally {
    orderLoading.value = false
  }
}
function resetOrderQuery() {
  orderQuery.order_no = ''
  orderQuery.keyword = ''
  orderQuery.order_type = undefined
  orderQuery.status = undefined
  orderQuery.page = 1
  loadOrders()
}
function handleRefund(row: OrderItem) {
  ElMessageBox.prompt('请输入退款原因', '订单退款', {
    inputType: 'textarea',
    confirmButtonText: '提交退款',
    cancelButtonText: '取消',
    inputValidator: (val) => (val && val.trim() ? true : '请填写退款原因'),
  })
    .then(async ({ value }) => {
      try {
        await refundOrder(row.id, { reason: value.trim() })
        ElMessage.success('退款状态已记录，资金退回待微信支付接入后执行')
        loadOrders()
      } catch {
        // 错误已由拦截器提示
      }
    })
    .catch(() => {})
}

/* ---------- 会员套餐 Tab ---------- */
const planLoading = ref(false)
const planList = ref<MemberPlanItem[]>([])
const planDialog = ref(false)
const planSubmitting = ref(false)
const planFormRef = ref<FormInstance>()
const editPlanId = ref<number | null>(null)
const planBenefitsText = ref('')

const planForm = reactive({
  name: '',
  level: 1,
  duration_days: 30,
  price_amount: 0,
  origin_amount: 0,
  description: '',
  benefits: [] as string[],
  ai_import_quota: 0,
  is_recommend: 0,
  sort_order: 0,
  status: PLAN_STATUS_ONLINE,
})

async function loadPlans() {
  planLoading.value = true
  try {
    planList.value = (await fetchMemberPlans()).list
  } finally {
    planLoading.value = false
  }
}

function openPlanEdit(row: MemberPlanItem) {
  editPlanId.value = row.id
  Object.assign(planForm, {
    name: row.name,
    level: row.level,
    duration_days: row.duration_days,
    price_amount: row.price_amount,
    origin_amount: row.origin_amount,
    description: row.description,
    benefits: row.benefits,
    ai_import_quota: row.ai_import_quota,
    is_recommend: row.is_recommend,
    sort_order: row.sort_order,
    status: row.status,
  })
  planBenefitsText.value = (row.benefits || []).join('\n')
  planDialog.value = true
}

async function submitPlan() {
  if (editPlanId.value === null) return
  planSubmitting.value = true
  try {
    const benefits = planBenefitsText.value
      .split('\n')
      .map((s) => s.trim())
      .filter(Boolean)
    await updateMemberPlan(editPlanId.value, {
      name: planForm.name,
      level: planForm.level,
      duration_days: planForm.duration_days,
      price_amount: planForm.price_amount,
      origin_amount: planForm.origin_amount,
      description: planForm.description,
      benefits,
      ai_import_quota: planForm.ai_import_quota,
      is_recommend: planForm.is_recommend,
      sort_order: planForm.sort_order,
      status: planForm.status,
    })
    ElMessage.success('保存成功')
    planDialog.value = false
    loadPlans()
  } finally {
    planSubmitting.value = false
  }
}

onMounted(() => {
  loadOrders()
  loadPlans()
})
</script>

<style scoped lang="scss">
.filter-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 16px;
  align-items: center;
}
.sub {
  color: #909399;
  font-size: 12px;
}
.pager {
  margin-top: 16px;
  justify-content: flex-end;
}
</style>
