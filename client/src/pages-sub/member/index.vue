<template>
  <view class="member">
    <!-- 当前会员状态卡 -->
    <view class="member__hero">
      <view class="member__hero-info">
        <text class="member__hero-level">{{ isVip ? levelText : '未开通会员' }}</text>
        <text class="member__hero-desc">
          {{ isVip ? `有效期至 ${profile?.member_expired_at ?? ''}` : '开通会员，解锁全部 AI 学习能力' }}
        </text>
      </view>
      <text class="member__hero-badge">VIP</text>
    </view>

    <!-- 套餐卡片列表 -->
    <view class="member__list">
      <view
        v-for="plan in plans"
        :key="plan.id"
        class="member__plan"
        :class="{ 'is-recommend': plan.is_recommend }"
      >
        <view v-if="plan.is_recommend" class="member__plan-tag">推荐</view>
        <view class="member__plan-header">
          <text class="member__plan-name">{{ plan.name }}</text>
          <text class="member__plan-duration">{{ durationText(plan) }}</text>
        </view>
        <view class="member__plan-price-row">
          <view class="member__plan-price">
            <text class="member__plan-price-unit">¥</text>
            <text class="member__plan-price-value">{{ plan.price_amount }}</text>
          </view>
          <text v-if="plan.origin_amount > plan.price_amount" class="member__plan-origin">
            ¥{{ plan.origin_amount }}
          </text>
        </view>
        <view class="member__plan-benefits">
          <view v-for="benefit in plan.benefits" :key="benefit" class="member__plan-benefit">
            <text class="member__plan-benefit-dot">✓</text>
            <text class="member__plan-benefit-text">{{ benefit }}</text>
          </view>
          <view class="member__plan-benefit">
            <text class="member__plan-benefit-dot">✓</text>
            <text class="member__plan-benefit-text">AI 导题配额 {{ quotaText(plan) }}</text>
          </view>
        </view>
        <view class="member__plan-btn" @tap="handleBuy(plan)">立即开通</view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 会员中心（P-23）
 * 接口：API-MBR-001 会员套餐列表（GET /member/plans）
 * 说明：顶部展示当前用户会员状态（API-USER-001 profile 的 member_level / member_expired_at）；
 *       「立即开通」依赖微信支付（API-PAY-001/002 未接入），真实下单 createMemberOrder 留 TODO。
 */
import { computed, onMounted, ref } from 'vue'
import { useUserStore } from '@/stores/user'
import { fetchMemberPlans } from '@/api/member'
import type { MemberPlan } from '@/types'

const MEMBER_LEVEL_TEXT: Record<number, string> = {
  1: '月卡会员',
  2: '季卡会员',
  3: '年卡会员',
  4: '永久会员'
}

const userStore = useUserStore()
const plans = ref<MemberPlan[]>([])

const profile = computed(() => userStore.profile)
const isVip = computed(() => userStore.isVip)
const levelText = computed(() =>
  profile.value ? MEMBER_LEVEL_TEXT[profile.value.member_level] ?? '会员' : '未开通会员'
)

onMounted(async () => {
  userStore.fetchProfileIfLogged()
  const res = await fetchMemberPlans()
  plans.value = res.list
})

function durationText(plan: MemberPlan): string {
  return plan.duration_days ? `${plan.duration_days} 天` : '永久有效'
}

function quotaText(plan: MemberPlan): string {
  return plan.ai_import_quota >= 9999 ? '不限次' : `${plan.ai_import_quota} 次`
}

function handleBuy(plan: MemberPlan) {
  // TODO: 真实下单：await createMemberOrder(plan.id) 创建待支付订单后拉起微信支付（API-PAY-001/002 待接入）
  uni.showToast({ title: '支付功能待微信支付接入，敬请期待', icon: 'none' })
}
</script>

<style lang="scss" scoped>
.member {
  min-height: 100vh;
  background-color: $color-bg-page;
  padding-bottom: $spacing-xl;

  &__hero {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: $spacing-lg;
    padding: $spacing-lg;
    background: linear-gradient(135deg, #3b2f2a 0%, #6b5644 100%);
    border-radius: $radius-xl;
  }

  &__hero-level {
    display: block;
    font-size: $font-size-xl;
    font-weight: 700;
    color: #f7d9a0;
  }

  &__hero-desc {
    display: block;
    margin-top: $spacing-xs;
    font-size: $font-size-xs;
    color: rgba(247, 217, 160, 0.8);
  }

  &__hero-badge {
    font-size: $font-size-xl;
    font-weight: 700;
    font-style: italic;
    color: #3b2f2a;
    background: linear-gradient(135deg, #f7d9a0 0%, #e8b86d 100%);
    padding: 10rpx 24rpx;
    border-radius: $radius-lg;
  }

  &__list {
    padding: 0 $spacing-lg;
  }

  &__plan {
    position: relative;
    margin-bottom: $spacing-md;
    padding: $spacing-lg;
    background-color: $color-bg-card;
    border: 2rpx solid transparent;
    border-radius: $radius-xl;
    box-shadow: $shadow-sm;

    &.is-recommend {
      border-color: $color-primary;
    }
  }

  &__plan-tag {
    position: absolute;
    top: 0;
    right: 0;
    padding: 4rpx 20rpx;
    font-size: $font-size-xs;
    color: $color-text-inverse;
    background-color: $color-danger;
    border-radius: 0 $radius-xl 0 $radius-lg;
  }

  &__plan-header {
    display: flex;
    align-items: baseline;
  }

  &__plan-name {
    font-size: $font-size-lg;
    font-weight: 700;
    color: $color-text-primary;
  }

  &__plan-duration {
    margin-left: $spacing-sm;
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__plan-price-row {
    display: flex;
    align-items: baseline;
    margin-top: $spacing-sm;
  }

  &__plan-price {
    display: flex;
    align-items: baseline;
  }

  &__plan-price-unit {
    font-size: $font-size-sm;
    color: $color-danger;
    font-weight: 600;
  }

  &__plan-price-value {
    font-size: $font-size-xxl;
    font-weight: 700;
    color: $color-danger;
  }

  &__plan-origin {
    margin-left: $spacing-sm;
    font-size: $font-size-xs;
    color: $color-text-placeholder;
    text-decoration: line-through;
  }

  &__plan-benefits {
    display: flex;
    flex-wrap: wrap;
    margin-top: $spacing-md;
  }

  &__plan-benefit {
    display: flex;
    align-items: center;
    width: 50%;
    margin-bottom: $spacing-xs;
  }

  &__plan-benefit-dot {
    margin-right: $spacing-xs;
    font-size: $font-size-xs;
    color: $color-success;
  }

  &__plan-benefit-text {
    font-size: $font-size-xs;
    color: $color-text-regular;
  }

  &__plan-btn {
    margin-top: $spacing-md;
    height: 76rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: $font-size-base;
    font-weight: 600;
    color: $color-text-inverse;
    background: linear-gradient(90deg, $color-primary 0%, #5a9bff 100%);
    border-radius: 38rpx;
  }
}
</style>
