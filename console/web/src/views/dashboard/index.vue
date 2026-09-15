<template>
  <div class="page-container">
    <!-- 统计卡片 -->
    <el-row :gutter="16">
      <el-col v-for="card in statCards" :key="card.label" :span="6">
        <el-card shadow="hover" class="stat-card">
          <div class="stat-card__label">{{ card.label }}</div>
          <div class="stat-card__value">{{ card.value }}</div>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="16" class="dashboard-row">
      <!-- 趋势图 -->
      <el-col :span="16">
        <el-card shadow="hover" class="dashboard-chart">
          <template #header>
            <div class="dashboard-chart__title">近 30 天学习趋势（每日作答数）</div>
          </template>
          <div v-if="hasTrend" class="bar-chart">
            <el-tooltip
              v-for="item in trend"
              :key="item.date"
              :content="`${item.date}　作答 ${item.answer_count} 道 / 正确 ${item.right_count} 道`"
              placement="top"
            >
              <div class="bar-chart__item">
                <div class="bar-chart__value">{{ item.answer_count }}</div>
                <div class="bar-chart__column" :style="{ height: barHeight(item.answer_count) }" />
                <div class="bar-chart__label">{{ item.date.slice(5) }}</div>
              </div>
            </el-tooltip>
          </div>
          <el-empty v-else description="暂无数据" />
        </el-card>
      </el-col>

      <!-- 会员状态卡片 -->
      <el-col :span="8">
        <el-card shadow="hover" class="member-card">
          <template #header>我的会员</template>
          <div v-if="member" class="member-card__body">
            <div class="member-card__level">
              {{ member.level_text }}
              <el-tag
                :type="MEMBER_STATUS_MAP[member.status]?.type || 'info'"
                size="small"
                class="member-card__status"
              >
                {{ MEMBER_STATUS_MAP[member.status]?.label || '未知' }}
              </el-tag>
            </div>
            <div class="member-card__row">
              <span class="member-card__key">到期时间</span>
              <span class="member-card__val">{{ member.expired_at || '—' }}</span>
            </div>
            <div class="member-card__row">
              <span class="member-card__key">AI 导题配额</span>
              <span class="member-card__val">{{ member.ai_import_quota }} 次</span>
            </div>
            <el-button
              v-if="member.level === 0"
              type="primary"
              class="member-card__btn"
              @click="goOrders"
            >
              开通会员
            </el-button>
          </div>
          <el-empty v-else description="加载中..." :image-size="60" />
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { fetchOverview } from '@/api/statistics'
import { MEMBER_STATUS_MAP } from '@/constants'
import type { StatisticsOverview, TrendItem } from '@/types/api.d'

const router = useRouter()
const overview = ref<StatisticsOverview | null>(null)
const trend = ref<TrendItem[]>([])

const member = computed(() => overview.value?.member ?? null)
const hasTrend = computed(() => trend.value.length > 0)
const maxAnswer = computed(() => Math.max(1, ...trend.value.map((i) => i.answer_count)))

const statCards = computed(() => {
  const s = overview.value?.summary
  if (!s) return []
  return [
    { label: '题库数', value: s.bank_count },
    { label: '题目数', value: s.question_count },
    { label: '练习次数', value: s.practice_count },
    { label: '考试次数', value: s.exam_count },
    { label: '正确率', value: `${s.correct_rate}%` },
    { label: '学习时长', value: formatDuration(s.duration_seconds) },
    { label: '错题数', value: s.wrong_question_count },
    { label: '收藏数', value: s.favorite_count },
  ]
})

function formatDuration(seconds: number): string {
  const h = Math.floor(seconds / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  if (h > 0) return m > 0 ? `${h}小时${m}分` : `${h}小时`
  if (m > 0) return `${m}分`
  return `${seconds}秒`
}

function barHeight(count: number) {
  return `${Math.round((count / maxAnswer.value) * 100)}%`
}

function goOrders() {
  router.push('/orders')
}

onMounted(async () => {
  try {
    const data = await fetchOverview()
    overview.value = data
    trend.value = data.trend
  } catch {
    // 错误已统一提示
  }
})
</script>

<style scoped lang="scss">
.stat-card {
  margin-bottom: 16px;

  &__label {
    font-size: 13px;
    color: #6b7280;
  }

  &__value {
    margin-top: 8px;
    font-size: 26px;
    font-weight: 700;
    color: #1f2937;
  }
}

.dashboard-row {
  margin-top: 0;
}

.dashboard-chart {
  &__title {
    font-weight: 600;
  }
}

.member-card {
  &__level {
    font-size: 20px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  &__row {
    display: flex;
    justify-content: space-between;
    margin-top: 14px;
    font-size: 14px;
  }

  &__key {
    color: #6b7280;
  }

  &__val {
    color: #1f2937;
    font-weight: 500;
  }

  &__btn {
    width: 100%;
    margin-top: 20px;
  }
}
</style>
