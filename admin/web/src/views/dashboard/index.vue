<template>
  <div class="page-container">
    <el-row :gutter="16" class="stat-grid" justify="start">
      <el-col :span="8">
        <el-card shadow="hover">
          <div class="stat-card">
            <div class="stat-card__label">用户总数</div>
            <div class="stat-card__value">{{ summary.user_total }}</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card shadow="hover">
          <div class="stat-card">
            <div class="stat-card__label">今日新增用户</div>
            <div class="stat-card__value">{{ summary.user_new_today }}</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card shadow="hover">
          <div class="stat-card">
            <div class="stat-card__label">题库总数</div>
            <div class="stat-card__value">{{ summary.bank_total }}</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card shadow="hover" class="stat-card--alert" @click="goBankAudit">
          <div class="stat-card">
            <div class="stat-card__label">待审核题库</div>
            <div class="stat-card__value stat-card__value--alert">{{ summary.bank_pending_audit }}</div>
            <div class="stat-card__hint">点击前往审核 →</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card shadow="hover">
          <div class="stat-card">
            <div class="stat-card__label">题目总数</div>
            <div class="stat-card__value">{{ summary.question_total }}</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card shadow="hover">
          <div class="stat-card">
            <div class="stat-card__label">进行中导入任务</div>
            <div class="stat-card__value">{{ summary.import_running }}</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-card shadow="hover" class="dashboard-chart">
      <template #header>近 7 天登录趋势</template>
      <div v-if="hasTrend" class="bar-chart">
        <div v-for="item in summary.login_7d" :key="item.date" class="bar-chart__item">
          <div class="bar-chart__value">{{ item.count }}</div>
          <div class="bar-chart__column" :style="{ height: barHeight(item.count) }" />
          <div class="bar-chart__label">{{ item.date.slice(5) }}</div>
        </div>
      </div>
      <el-empty v-else description="暂无数据" />
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { fetchDashboardSummary } from '@/api/dashboard'
import type { DashboardSummary } from '@/types/api.d'

const router = useRouter()
const summary = ref<DashboardSummary>({
  user_total: 0,
  user_new_today: 0,
  bank_total: 0,
  bank_pending_audit: 0,
  question_total: 0,
  import_running: 0,
  login_7d: [],
})

const hasTrend = computed(() => summary.value.login_7d.length > 0)
const maxCount = computed(() => Math.max(1, ...summary.value.login_7d.map((i) => i.count)))

function barHeight(count: number) {
  // 最高占 100%，其余按比例
  return `${Math.round((count / maxCount.value) * 100)}%`
}

function goBankAudit() {
  router.push('/bank/banks')
}

onMounted(async () => {
  try {
    summary.value = await fetchDashboardSummary()
  } catch {
    // 错误已统一提示
  }
})
</script>

<style scoped lang="scss">
.stat-card {
  padding: 8px 4px;

  &__label {
    font-size: 13px;
    color: #6b7280;
  }

  &__value {
    margin-top: 8px;
    font-size: 28px;
    font-weight: 700;
    color: #1f2937;

    &--alert {
      color: #f59e0b;
    }
  }

  &__hint {
    margin-top: 6px;
    font-size: 12px;
    color: #2563eb;
  }
}

.stat-card--alert {
  cursor: pointer;
}

.dashboard-chart {
  margin-top: 16px;
}
</style>
