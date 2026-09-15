<template>
  <div class="page-container">
    <!-- 会员信息卡 + 套餐列表 -->
    <el-row :gutter="16">
      <el-col :span="8">
        <el-card shadow="hover" class="member-card">
          <template #header>
            <div class="member-card__head">
              <span>我的会员</span>
              <el-tag
                v-if="member"
                :type="MEMBER_STATUS_MAP[member.status]?.type || 'info'"
                size="small"
              >
                {{ MEMBER_STATUS_MAP[member.status]?.label || member.status_text }}
              </el-tag>
            </div>
          </template>
          <div v-loading="memberLoading" class="member-card__body">
            <div class="member-card__level">{{ member?.level_text || '普通用户' }}</div>
            <div class="member-card__row">
              <span class="member-card__key">生效时间</span>
              <span class="member-card__val">{{ member?.started_at || '—' }}</span>
            </div>
            <div class="member-card__row">
              <span class="member-card__key">到期时间</span>
              <span class="member-card__val">{{ member?.expired_at || '—' }}</span>
            </div>
            <div class="member-card__row">
              <span class="member-card__key">AI 导题配额</span>
              <span class="member-card__val">{{ member?.ai_import_quota ?? 0 }} 次</span>
            </div>
            <div class="member-card__row">
              <span class="member-card__key">开通来源</span>
              <span class="member-card__val">{{ member?.source_type_text || '—' }}</span>
            </div>
          </div>
        </el-card>
      </el-col>

      <el-col :span="16">
        <el-card shadow="hover" class="plan-card">
          <template #header>会员套餐</template>
          <div v-loading="planLoading" class="plan-list">
            <div
              v-for="plan in plans"
              :key="plan.id"
              class="plan-item"
              :class="{ 'is-recommend': plan.is_recommend === 1 }"
            >
              <div class="plan-item__head">
                <span class="plan-item__name">{{ plan.name }}</span>
                <el-tag v-if="plan.is_recommend === 1" type="danger" size="small">推荐</el-tag>
              </div>
              <div class="plan-item__price">
                <span class="plan-item__now">¥{{ plan.price_amount }}</span>
                <span v-if="plan.origin_amount !== plan.price_amount" class="plan-item__origin">
                  ¥{{ plan.origin_amount }}
                </span>
              </div>
              <div class="plan-item__meta">
                {{ plan.duration_days }} 天 · AI 导题 {{ plan.ai_import_quota }} 次
              </div>
              <ul v-if="plan.benefits?.length" class="plan-item__benefits">
                <li v-for="(b, i) in plan.benefits" :key="i">{{ b }}</li>
              </ul>
              <div v-if="plan.description" class="plan-item__desc">{{ plan.description }}</div>
            </div>
            <el-empty v-if="!planLoading && plans.length === 0" description="暂无可用套餐" :image-size="60" />
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 我的订单 -->
    <el-card shadow="never" class="order-card">
      <template #header>我的订单</template>

      <div class="filter-bar">
        <el-select v-model="query.order_type" placeholder="订单类型" clearable style="width: 160px" @change="loadOrders">
          <el-option v-for="(v, k) in ORDER_TYPE_MAP" :key="k" :label="v" :value="Number(k)" />
        </el-select>
        <el-select v-model="query.status" placeholder="订单状态" clearable style="width: 160px" @change="loadOrders">
          <el-option v-for="(v, k) in ORDER_STATUS_MAP" :key="k" :label="v.label" :value="Number(k)" />
        </el-select>
        <el-button type="primary" :icon="Search" @click="loadOrders">查询</el-button>
        <el-button :icon="Refresh" @click="resetQuery">重置</el-button>
      </div>

      <el-table v-loading="loading" :data="list" border stripe>
        <el-table-column prop="order_no" label="订单号" width="210" show-overflow-tooltip />
        <el-table-column label="类型" width="110">
          <template #default="{ row }">{{ row.order_type_text }}</template>
        </el-table-column>
        <el-table-column prop="biz_title" label="商品" min-width="150" show-overflow-tooltip />
        <el-table-column prop="origin_amount" label="原价" width="90" />
        <el-table-column prop="discount_amount" label="优惠" width="90" />
        <el-table-column label="实付" width="100">
          <template #default="{ row }">
            <span class="order-pay">¥{{ row.pay_amount }}</span>
          </template>
        </el-table-column>
        <el-table-column label="支付方式" width="110">
          <template #default="{ row }">{{ row.pay_channel_text || '—' }}</template>
        </el-table-column>
        <el-table-column label="状态" width="90">
          <template #default="{ row }">
            <el-tag :type="ORDER_STATUS_MAP[row.status]?.type || 'info'">
              {{ ORDER_STATUS_MAP[row.status]?.label || row.status_text }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="paid_at" label="支付时间" width="170">
          <template #default="{ row }">{{ row.paid_at || '—' }}</template>
        </el-table-column>
        <el-table-column prop="created_at" label="下单时间" width="170" />
        <el-table-column label="操作" width="90" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="openDetail(row)">详情</el-button>
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
        @current-change="loadOrders"
        @size-change="loadOrders"
      />
    </el-card>

    <!-- 订单详情抽屉 -->
    <el-drawer v-model="detailVisible" title="订单详情" size="480px">
      <el-descriptions v-if="detail" :column="1" border>
        <el-descriptions-item label="订单号">{{ detail.order_no }}</el-descriptions-item>
        <el-descriptions-item label="订单类型">{{ detail.order_type_text }}</el-descriptions-item>
        <el-descriptions-item label="商品名称">{{ detail.biz_title }}</el-descriptions-item>
        <el-descriptions-item label="原价">¥{{ detail.origin_amount }}</el-descriptions-item>
        <el-descriptions-item label="优惠金额">¥{{ detail.discount_amount }}</el-descriptions-item>
        <el-descriptions-item label="实付金额">
          <span class="order-pay">¥{{ detail.pay_amount }}</span>
        </el-descriptions-item>
        <el-descriptions-item label="支付方式">{{ detail.pay_channel_text || '—' }}</el-descriptions-item>
        <el-descriptions-item label="订单状态">
          <el-tag :type="ORDER_STATUS_MAP[detail.status]?.type || 'info'">
            {{ ORDER_STATUS_MAP[detail.status]?.label || detail.status_text }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="支付时间">{{ detail.paid_at || '—' }}</el-descriptions-item>
        <el-descriptions-item label="下单时间">{{ detail.created_at }}</el-descriptions-item>
      </el-descriptions>
    </el-drawer>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { Search, Refresh } from '@element-plus/icons-vue'
import { fetchOrders, fetchOrderDetail, fetchMember, fetchMemberPlans } from '@/api/order'
import {
  ORDER_TYPE_MAP,
  ORDER_STATUS_MAP,
  MEMBER_STATUS_MAP,
  DEFAULT_PAGE_SIZE,
  PAGE_SIZE_OPTIONS,
} from '@/constants'
import type { MemberInfo, MemberPlan, OrderItem } from '@/types/api.d'

const memberLoading = ref(false)
const member = ref<MemberInfo | null>(null)

const planLoading = ref(false)
const plans = ref<MemberPlan[]>([])

const loading = ref(false)
const list = ref<OrderItem[]>([])
const total = ref(0)

const query = reactive({
  order_type: undefined as number | undefined,
  status: undefined as number | undefined,
  page: 1,
  page_size: DEFAULT_PAGE_SIZE,
})

const detailVisible = ref(false)
const detail = ref<OrderItem | null>(null)

async function loadMember() {
  memberLoading.value = true
  try {
    member.value = await fetchMember()
  } finally {
    memberLoading.value = false
  }
}

async function loadPlans() {
  planLoading.value = true
  try {
    plans.value = await fetchMemberPlans()
  } finally {
    planLoading.value = false
  }
}

async function loadOrders() {
  loading.value = true
  try {
    const res = await fetchOrders({ ...query })
    list.value = res.list
    total.value = res.pagination.total
  } finally {
    loading.value = false
  }
}

function resetQuery() {
  query.order_type = undefined
  query.status = undefined
  query.page = 1
  loadOrders()
}

async function openDetail(row: OrderItem) {
  try {
    detail.value = await fetchOrderDetail(row.id)
    detailVisible.value = true
  } catch {
    // 错误已由 request 拦截器统一提示
  }
}

onMounted(() => {
  loadMember()
  loadPlans()
  loadOrders()
})
</script>

<style scoped lang="scss">
.member-card {
  letter-spacing: normal;

  &__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  &__body {
    min-height: 168px;
  }

  &__level {
    font-size: 22px;
    font-weight: 700;
    color: var(--el-color-primary);
  }

  &__row {
    display: flex;
    justify-content: space-between;
    margin-top: 12px;
    font-size: 13px;
  }

  &__key {
    color: #6b7280;
  }

  &__val {
    color: #1f2937;
    font-weight: 500;
  }
}

.plan-card {
  min-height: 260px;
}

.plan-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 12px;
  min-height: 168px;
}

.plan-item {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 12px;

  &.is-recommend {
    border-color: var(--el-color-primary);
    background: var(--el-color-primary-light-9);
  }

  &__head {
    display: flex;
    align-items: center;
    gap: 6px;
  }

  &__name {
    font-weight: 600;
    color: #1f2937;
  }

  &__price {
    margin-top: 8px;
  }

  &__now {
    font-size: 20px;
    font-weight: 700;
    color: #f56c6c;
  }

  &__origin {
    margin-left: 6px;
    font-size: 12px;
    color: #9ca3af;
    text-decoration: line-through;
  }

  &__meta {
    margin-top: 6px;
    font-size: 12px;
    color: #6b7280;
  }

  &__benefits {
    margin: 8px 0 0;
    padding-left: 18px;
    font-size: 12px;
    color: #4b5563;
    line-height: 1.9;
  }

  &__desc {
    margin-top: 6px;
    font-size: 12px;
    color: #9ca3af;
  }
}

.order-card {
  margin-top: 16px;
}

.order-pay {
  color: #f56c6c;
  font-weight: 600;
}
</style>
