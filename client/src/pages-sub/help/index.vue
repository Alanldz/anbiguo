<template>
  <view class="help">
    <!-- 标题区 -->
    <view class="help__header">
      <text class="help__title">帮助中心</text>
      <text class="help__meta">常见问题与使用说明</text>
    </view>

    <!-- FAQ 手风琴 -->
    <view class="help__list">
      <view v-for="(item, index) in faqList" :key="item.q" class="help__item">
        <view class="help__question" @tap="toggle(index)">
          <text class="help__question-text">{{ item.q }}</text>
          <text class="help__question-arrow" :class="{ 'is-open': openIndex === index }">›</text>
        </view>
        <view v-if="openIndex === index" class="help__answer">
          <text class="help__answer-text">{{ item.a }}</text>
        </view>
      </view>
    </view>

    <!-- 落款 -->
    <view class="help__footer">
      <text class="help__footer-text">识途刷题团队 · v1.0</text>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 帮助中心（P-32）
 * 说明：静态 FAQ 手风琴页（自实现折叠，不依赖 uni-collapse），
 *       内容为前端静态文案，随版本更新维护；入口：设置页「关于」组、我的页「更多功能」。
 */
import { ref } from 'vue'

interface FaqItem {
  q: string
  a: string
}

const faqList: FaqItem[] = [
  {
    q: '如何导入题库？',
    a: '进入首页或题库详情，点击「文档导题」上传 doc/docx/xls/xlsx/pdf/txt 或图片文件，系统解析后可在「解析进度」页校对入库；也可以在「手动导题」中逐题录入。'
  },
  {
    q: '手动录题支持哪些题型？',
    a: '目前支持单选题、多选题、判断题、填空题与简答题。选择题需填写选项内容并标注正确答案，填空/简答题直接填写参考答案与解析即可。'
  },
  {
    q: '练习模式有哪些区别？',
    a: '顺序练习按题目顺序逐题作答；随机练习打乱顺序；专项练习按章节/考点针对性训练。提交答案后即时判分并展示解析，可随时通过答题卡跳题。'
  },
  {
    q: '错题本和斩题是怎么运作的？',
    a: '答错的题目自动进入错题本，可随时重做巩固。同一道题连续答对 3 次会被自动「斩掉」，不再出现在练习与错题重做中；已斩题目可在「我的斩题」中回顾。'
  },
  {
    q: 'VIP 会员有哪些权益？',
    a: '会员可使用 AI 导题、精简题、易错题集等 45+ 项权益，并解锁 VIP 题库市场内容。权益在会员有效期内生效，到期后自动停止，可在会员中心随时续费。'
  },
  {
    q: '注销账号后数据还能恢复吗？',
    a: '可以。账号注销后 30 天内可联系客服申请恢复，逾期将删除或匿名化您的全部个人信息，届时数据将无法找回。'
  },
  {
    q: '我的学习数据与隐私如何保护？',
    a: '练习记录、错题、收藏等数据仅用于生成学习统计与复习提醒，存储于境内服务器并加密传输。详见「设置 → 关于 → 隐私政策」。'
  },
  {
    q: '如何反馈问题或建议？',
    a: '在「我的 → 更多功能 → 意见反馈」中提交，支持截图与联系方式，我们会在 1~3 个工作日内处理；试题内容有误也可在答题页点击「报错」。'
  },
  {
    q: '考试模式有什么规则？',
    a: '模拟考试按设定时长倒计时，到时自动交卷；交卷后立即出分，可查看逐题解析与成绩单。交卷前可使用答题卡检查未答题，交卷后不可修改答案。'
  }
]

/** 当前展开项索引，-1 表示全部收起（单开模式） */
const openIndex = ref(-1)

function toggle(index: number) {
  openIndex.value = openIndex.value === index ? -1 : index
}
</script>

<style lang="scss" scoped>
.help {
  min-height: 100vh;
  padding: $spacing-lg;
  background-color: $color-bg-page;

  &__header {
    padding: $spacing-md 0;
    text-align: center;
  }

  &__title {
    display: block;
    font-size: $font-size-xl;
    font-weight: 700;
    color: $color-text-primary;
  }

  &__meta {
    display: block;
    margin-top: $spacing-xs;
    font-size: $font-size-xs;
    color: $color-text-placeholder;
  }

  &__list {
    background-color: $color-bg-card;
    border-radius: $radius-lg;
    overflow: hidden;
  }

  &__item {
    border-bottom: 2rpx solid $color-divider;

    &:last-child {
      border-bottom: none;
    }
  }

  &__question {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: $spacing-md;
  }

  &__question-text {
    flex: 1;
    font-size: $font-size-base;
    font-weight: 600;
    color: $color-text-primary;
  }

  &__question-arrow {
    margin-left: $spacing-sm;
    font-size: $font-size-lg;
    color: $color-text-placeholder;
    transform: rotate(90deg);
    transition: transform 0.2s;

    &.is-open {
      transform: rotate(-90deg);
    }
  }

  &__answer {
    padding: 0 $spacing-md $spacing-md;
  }

  &__answer-text {
    font-size: $font-size-sm;
    line-height: 1.7;
    color: $color-text-regular;
  }

  &__footer {
    padding: $spacing-lg 0;
    text-align: center;
  }

  &__footer-text {
    font-size: $font-size-sm;
    color: $color-text-secondary;
  }
}
</style>
