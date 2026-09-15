<template>
  <view class="agree">
    <!-- 标题区 -->
    <view class="agree__header">
      <text class="agree__title">{{ doc.title }}</text>
      <text class="agree__meta">版本：{{ doc.version }}　生效日期：{{ doc.effectiveDate }}</text>
    </view>

    <!-- 前言 -->
    <view class="agree__intro">
      <text class="agree__text">{{ doc.intro }}</text>
    </view>

    <!-- 条款列表 -->
    <view class="agree__section">
      <text class="agree__section-title">{{ doc.title }}条款</text>
      <view v-for="(clause, index) in doc.clauses" :key="index" class="agree__clause">
        <text class="agree__clause-title">{{ index + 1 }}. {{ clause.title }}</text>
        <text class="agree__text">{{ clause.content }}</text>
      </view>
    </view>

    <!-- 落款 -->
    <view class="agree__footer">
      <text class="agree__footer-text">识途刷题团队</text>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 协议详情（P-31）
 * 说明：静态协议页，接收 ?type=privacy（隐私政策）| service（用户协议），
 *       内容为前端静态文案（data 数组渲染），随版本更新维护。
 */
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'

interface Clause {
  title: string
  content: string
}

interface AgreementDoc {
  title: string
  version: string
  effectiveDate: string
  intro: string
  clauses: Clause[]
}

const PRIVACY_DOC: AgreementDoc = {
  title: '《隐私政策》',
  version: 'v1.0',
  effectiveDate: '2026-09-15',
  intro:
    '识途刷题（以下简称"本产品"）由识途刷题团队提供。我们深知个人信息对您的重要性，将以高度审慎的态度处理您的个人信息。请您在使用本产品前仔细阅读本政策。',
  clauses: [
    {
      title: '信息收集范围',
      content:
        '为提供刷题服务，我们会收集：账号信息（手机号、昵称、头像）、学习行为数据（练习记录、错题、收藏、笔记）、订单与支付信息。我们不会收集与服务无关的通讯录、短信等敏感信息。'
    },
    {
      title: '信息使用目的',
      content:
        '收集的信息仅用于：生成学习统计与错题分析、提供斩题与复习提醒、订单履约与会员权益发放、改进产品功能与体验。不会将您的学习数据用于无关目的。'
    },
    {
      title: '信息存储与保护',
      content:
        '您的数据存储于境内服务器，采用加密传输与访问控制等措施防止泄露、篡改或丢失。存储期限为实现上述目的所必需的最短时间，超出期限后将删除或匿名化处理。'
    },
    {
      title: '信息共享',
      content:
        '除以下情形外，我们不会向第三方共享您的个人信息：获得您的明确同意；为完成支付需向支付服务商提供必要订单信息；依据法律法规或有权机关的要求。'
    },
    {
      title: '您的权利',
      content:
        '您有权查询、更正、导出您的个人信息，有权删除收藏、笔记等学习数据，可在设置页注销账号。注销后我们将在合理期限内删除或匿名化您的全部个人信息。'
    },
    {
      title: '未成年人保护',
      content:
        '本产品主要面向成年人。若为未满 14 周岁的未成年人使用本产品，应在监护人陪同下阅读本政策并取得监护人同意后使用。'
    },
    {
      title: '政策更新',
      content:
        '我们可能适时更新本政策。重大变更时将通过站内通知等方式提醒您。若您在更新后继续使用本产品，即表示您同意更新后的政策。'
    },
    {
      title: '免责声明',
      content:
        '因不可抗力、黑客攻击等非因我们过错导致的个人信息泄露，我们将尽力协助补救但不承担由此产生的间接损失。题库内容由上传者提供，其准确性以官方发布为准。'
    }
  ]
}

const SERVICE_DOC: AgreementDoc = {
  title: '《用户协议》',
  version: 'v1.0',
  effectiveDate: '2026-09-15',
  intro:
    '欢迎使用识途刷题。本协议是您与识途刷题团队之间关于使用本产品服务的约定。您注册、登录或使用本产品，即视为已阅读并同意本协议全部条款。',
  clauses: [
    {
      title: '账号注册与安全',
      content:
        '您应以真实信息注册账号，并妥善保管账号密码。因您主动泄露密码或遭受他人攻击导致的损失由您自行承担。发现账号被盗用请立即联系我们。'
    },
    {
      title: '用户行为规范',
      content:
        '您承诺不利用本产品上传、传播违法违规内容，不通过技术手段批量抓取题库数据，不恶意刷量、刷单或干扰产品正常运行。'
    },
    {
      title: '题库内容约定',
      content:
        '您上传的题库内容应为您有权使用的资料，因上传内容引发的权利纠纷由您自行承担。平台有权对违规题库下架或删除处理。'
    },
    {
      title: '付费服务',
      content:
        'VIP 会员等付费服务按页面标示的价格与周期提供。会员权益在有效期内生效，到期后自动停止。因虚拟商品特性，支付成功后一般不支持退款，法律另有规定除外。'
    },
    {
      title: '知识产权',
      content:
        '本产品的界面设计、代码及相关内容的知识产权归识途刷题团队所有。用户上传题库的知识产权归属按上传者与平台另行约定执行。'
    },
    {
      title: '服务变更与中止',
      content:
        '我们有权基于业务调整对服务内容进行变更、中断或终止。对您违反本协议的行为，我们有权视情节采取警告、限制功能、封禁账号等措施。'
    },
    {
      title: '免责声明',
      content:
        '因不可抗力、网络故障、系统维护等原因导致服务暂不可用，我们不承担责任。练习与考试结果仅供学习参考，不构成对任何考试成绩的承诺。'
    },
    {
      title: '协议的生效与争议解决',
      content:
        '本协议自您确认接受之日起生效。因本协议产生的争议，双方应友好协商解决；协商不成的，任一方可向识途刷题团队所在地有管辖权的人民法院提起诉讼。'
    }
  ]
}

const docType = ref<'privacy' | 'service'>('service')

const doc = computed<AgreementDoc>(() => (docType.value === 'privacy' ? PRIVACY_DOC : SERVICE_DOC))

onLoad((options) => {
  docType.value = options?.type === 'privacy' ? 'privacy' : 'service'
})
</script>

<style lang="scss" scoped>
.agree {
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

  &__intro,
  &__section {
    margin-bottom: $spacing-md;
    padding: $spacing-md;
    background-color: $color-bg-card;
    border-radius: $radius-lg;
  }

  &__section-title {
    display: block;
    margin-bottom: $spacing-sm;
    font-size: $font-size-base;
    font-weight: 600;
    color: $color-text-primary;
  }

  &__clause {
    margin-bottom: $spacing-md;

    &:last-child {
      margin-bottom: 0;
    }
  }

  &__clause-title {
    display: block;
    margin-bottom: $spacing-xs;
    font-size: $font-size-sm;
    font-weight: 600;
    color: $color-text-primary;
  }

  &__text {
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
