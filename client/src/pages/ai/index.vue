<template>
  <view class="ai-page">
    <!-- 顶部渐变区 -->
    <view class="ai-page__header" :style="{ paddingTop: `${statusBarHeight + 12}px` }">
      <view class="ai-page__header-top">
        <view class="ai-page__brand">
          <text class="ai-page__brand-title">AI 导题</text>
          <text class="ai-page__brand-tag">免费</text>
        </view>
        <view class="ai-page__header-actions">
          <text class="ai-page__header-link" @tap="goTask">生成记录</text>
          <text class="ai-page__header-link" @tap="goResource">资料库</text>
        </view>
      </view>
      <text class="ai-page__slogan">一键将文档生成电子题库 · 手机刷题</text>

      <view class="ai-page__chips">
        <text v-for="chip in chips" :key="chip" class="ai-page__chip">{{ chip }}</text>
      </view>
    </view>

    <view class="ai-page__body">
      <!-- 上传卡片 -->
      <view class="ai-page__upload-card">
        <view class="ai-page__format-row">
          <view v-for="format in formats" :key="format" class="ai-page__format">
            <view class="ai-page__format-icon">{{ format.slice(0, 2) }}</view>
            <text class="ai-page__format-label">{{ format }}</text>
          </view>
        </view>

        <upload-panel
          :files="uploadFiles"
          :accept="acceptExtensions"
          hint="点击上传文件"
          sub-hint="支持 20+ 格式：Word / Excel / PDF / TXT / 图片"
          @choose="handleChooseFile"
          @remove="handleRemoveFile"
        />

        <text class="ai-page__tip">内容生成后，分享好友或群组，一起学习更高效</text>
      </view>

      <!-- 三种导题方式 -->
      <view class="ai-page__ways">
        <view v-for="way in ways" :key="way.key" class="ai-page__way" @tap="handleWay(way)">
          <text class="ai-page__way-title">{{ way.title }}</text>
          <text class="ai-page__way-desc">{{ way.desc }}</text>
          <text class="ai-page__way-icon">{{ way.iconText }}</text>
        </view>
      </view>

      <view class="ai-page__links">
        <text class="ai-page__link" @tap="handleDownloadTemplate">下载导入模版 ↓</text>
        <text class="ai-page__link" @tap="handleSplitUpload">试题与答案分开上传 ›</text>
      </view>

      <!-- 更多 AI 能力 -->
      <view class="section-title">
        <text class="section-title__text">更多 AI 能力</text>
      </view>
      <grid-menu :items="aiFeatures" :columns="3" @select="handleAiFeature" />
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * AI 导题页（P-03）
 * 接口：API-FIL-001 直传凭证、API-IMP-001 上传导题、API-IMP-005 拍照录题
 * 上传流程详见 docs/05-OSS存储与文件分类规范.md §四
 */
import { ref } from 'vue'
import { fetchImportTemplateUrl } from '@/api/importer'
import { getStatusBarHeight } from '@/utils/platform'
import { FileBizType } from '@/types'
import type { UploadFile } from '@/components/upload-panel/upload-panel.vue'
import type { GridMenuItem } from '@/components/grid-menu/grid-menu.vue'

const statusBarHeight = ref(getStatusBarHeight())
const uploadFiles = ref<UploadFile[]>([])
const chips = ['错题整理', '试题解析', '在线练习', '发起考试']
const formats = ['Word', 'Excel', 'PDF', 'TXT', '图片']
const acceptExtensions = ['doc', 'docx', 'xls', 'xlsx', 'pdf', 'txt', 'jpg', 'jpeg', 'png']

const ways = [
  { key: 'manual', title: '手动导题', desc: '单题/文档录入', iconText: '录', path: '/pages-sub/import/manual' },
  { key: 'human', title: '人工导题', desc: '专业内容团队录入', iconText: '人', path: '' },
  { key: 'photo', title: '拍照录题', desc: '自动提取图片试题', iconText: '拍', path: '/pages-sub/search/index?mode=photo' }
]

const aiFeatures: GridMenuItem[] = [
  { key: 'memory', label: '记忆卡', desc: '艾宾浩斯记忆法', iconText: '记', color: '#22C55E', bgColor: '#E8F8EE' },
  { key: 'course', label: 'AI 课程', desc: '文档资料变课程', iconText: '课', color: '#2B7CFF', bgColor: '#EAF2FF' },
  { key: 'points', label: '考点速记', desc: '临考提分必看', iconText: '考', color: '#F59E0B', bgColor: '#FFF5E6' },
  { key: 'generate', label: 'AI 出题', desc: '创建专属备考题库', iconText: 'AI', color: '#8B5CF6', bgColor: '#F3EEFE' }
]

function handleChooseFile(files: Array<{ path: string; name: string; size: number }>) {
  // 接入后端后：调 API-FIL-001 拿七牛直传凭证 → 上传 → API-FIL-002 登记 → API-IMP-001 建任务
  files.forEach((file, index) => {
    uploadFiles.value.push({
      originName: file.name,
      objectKey: `bank/temp/202609/${file.name}`,
      ext: file.name.split('.').pop() ?? '',
      size: file.size,
      progress: 100,
      status: 'success'
    })
    console.log('[ai-import] biz_type =', FileBizType.BankSource, 'index =', index)
  })

  uni.showModal({
    title: '上传成功',
    content: `已选择 ${files.length} 个文件。解析服务开通后即可自动生成题库。`,
    showCancel: false
  })
}

function handleRemoveFile(index: number) {
  uploadFiles.value.splice(index, 1)
}

function handleWay(way: { title: string; path: string }) {
  if (way.path) {
    uni.navigateTo({ url: way.path })
    return
  }
  uni.showToast({ title: `${way.title} 开发中`, icon: 'none' })
}

function handleAiFeature(item: GridMenuItem) {
  if (!item.path) uni.showToast({ title: `${item.label} 开发中`, icon: 'none' })
}

function handleDownloadTemplate() {
  const url = fetchImportTemplateUrl('xlsx')
  console.log('[ai-import] template url =', url)
  uni.showToast({ title: '模板下载开发中', icon: 'none' })
}

function handleSplitUpload() {
  uni.showModal({
    title: '试题与答案分开上传',
    content: '可同时上传题目文档与答案文档，系统自动匹配。该功能随解析服务一同上线。',
    showCancel: false
  })
}

function goTask() {
  uni.navigateTo({ url: '/pages-sub/import/task' })
}

function goResource() {
  uni.navigateTo({ url: '/pages-sub/resource/list' })
}
</script>

<style lang="scss" scoped>
.ai-page {
  min-height: 100vh;
  background-color: $color-bg-page;
  padding-bottom: $spacing-xl;

  &__header {
    padding: 0 $spacing-lg $spacing-xl;
    background: linear-gradient(135deg, #eef4ff 0%, #f6f0ff 100%);
  }

  &__header-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  &__brand {
    display: flex;
    align-items: center;
  }

  &__brand-title {
    font-size: $font-size-xxl;
    font-weight: 700;
    color: $color-primary;
  }

  &__brand-tag {
    margin-left: $spacing-sm;
    font-size: $font-size-xs;
    color: $color-text-inverse;
    background-color: $color-danger;
    padding: 2rpx 10rpx;
    border-radius: $radius-sm;
  }

  &__header-actions {
    display: flex;
    align-items: center;
  }

  &__header-link {
    font-size: $font-size-sm;
    color: $color-primary;
    background-color: rgba(43, 124, 255, 0.12);
    padding: 8rpx 20rpx;
    border-radius: 24rpx;
    margin-left: $spacing-sm;
  }

  &__slogan {
    display: block;
    margin-top: $spacing-md;
    font-size: $font-size-lg;
    font-weight: 600;
    color: $color-text-primary;
  }

  &__chips {
    display: flex;
    flex-wrap: wrap;
    margin-top: $spacing-md;
  }

  &__chip {
    font-size: $font-size-xs;
    color: $color-primary;
    background-color: $color-bg-card;
    border: 2rpx solid rgba(43, 124, 255, 0.3);
    padding: 6rpx 20rpx;
    border-radius: 24rpx;
    margin: 0 $spacing-sm $spacing-sm 0;
  }

  &__body {
    padding: 0 $spacing-lg;
    margin-top: -$spacing-lg;

    > * {
      margin-bottom: $spacing-md;
    }
  }

  &__upload-card {
    background: linear-gradient(180deg, #ffffff 0%, #eef4ff 100%);
    border-radius: $radius-xl;
    padding: $spacing-lg;
    box-shadow: $shadow-md;
  }

  &__format-row {
    display: flex;
    justify-content: space-around;
    margin-bottom: $spacing-lg;
  }

  &__format {
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  &__format-icon {
    width: 64rpx;
    height: 64rpx;
    border-radius: $radius-sm;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: $font-size-xs;
    color: $color-text-inverse;
    background-color: $color-primary;
    font-weight: 600;
  }

  &__format-label {
    margin-top: 6rpx;
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__tip {
    display: block;
    margin-top: $spacing-md;
    text-align: center;
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__ways {
    display: flex;
  }

  &__way {
    flex: 1;
    margin-right: $spacing-sm;
    padding: $spacing-md;
    border-radius: $radius-lg;
    background-color: $color-bg-card;
    position: relative;

    &:last-child {
      margin-right: 0;
    }
  }

  &__way-title {
    display: block;
    font-size: $font-size-sm;
    font-weight: 600;
    color: $color-text-primary;
  }

  &__way-desc {
    display: block;
    margin-top: 4rpx;
    font-size: $font-size-xs;
    color: $color-text-secondary;
  }

  &__way-icon {
    position: absolute;
    right: $spacing-md;
    bottom: $spacing-md;
    font-size: $font-size-lg;
    color: $color-primary-light;
  }

  &__links {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  &__link {
    font-size: $font-size-sm;
    color: $color-primary;
  }
}
</style>
