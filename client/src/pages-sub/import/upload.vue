<template>
  <view class="import">
    <view class="import__card">
      <text class="import__title">上传文档生成题库</text>
      <text class="import__desc">
        支持 Word / Excel / PDF / TXT / 图片，上传后自动识别题目，生成可手机刷题的电子题库。
      </text>

      <upload-panel
        :files="files"
        :accept="accept"
        hint="点击上传文件"
        sub-hint="单个文件不超过 50MB"
        @choose="handleChoose"
        @remove="handleRemove"
      />

      <view class="import__options">
        <view class="import__option">
          <text class="import__option-label">选择题库</text>
          <picker :range="bankNames" @change="handleBankChange">
            <text class="import__option-value">{{ selectedBankName || '新建题库' }} ›</text>
          </picker>
        </view>
        <view class="import__option">
          <text class="import__option-label">试题与答案分开上传</text>
          <switch :checked="splitAnswer" color="#2B7CFF" @change="handleSplitChange" />
        </view>
      </view>

      <view class="import__tips">
        <text class="import__tip">· 建议先下载标准模版，按模版整理题目，识别准确率更高</text>
        <text class="import__tip">· 题目中的图片、公式请单独上传，系统会自动关联</text>
        <text class="import__tip">· 解析完成后可逐题校对，确认无误再入库</text>
      </view>

      <view class="import__actions">
        <view class="import__btn import__btn--plain" @tap="handleDownloadTemplate">下载导入模版</view>
        <view class="import__btn import__btn--primary" @tap="handleSubmit">开始生成题库</view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
/**
 * 文档导题页（P-16）
 * 流程：上传到七牛（API-FIL-001/002）→ 创建导入任务（API-IMP-001）→ 跳转解析进度页
 * 详见 docs/05-OSS存储与文件分类规范.md §四
 */
import { computed, ref } from 'vue'
import { useBankStore } from '@/stores/bank'
import { createImportByUpload, fetchImportTemplateUrl } from '@/api/importer'
import { FileBizType } from '@/types'
import type { UploadFile } from '@/components/upload-panel/upload-panel.vue'

const bankStore = useBankStore()

const files = ref<UploadFile[]>([])
const accept = ['doc', 'docx', 'xls', 'xlsx', 'pdf', 'txt', 'jpg', 'jpeg', 'png']
const splitAnswer = ref(false)
const selectedBankIndex = ref(-1)

const bankNames = computed(() => ['新建题库', ...bankStore.banks.map((bank) => bank.title)])
const selectedBankName = computed(() =>
  selectedBankIndex.value >= 0 ? bankStore.banks[selectedBankIndex.value]?.title : ''
)

function handleChoose(selected: Array<{ path: string; name: string; size: number }>) {
  selected.forEach((file) => {
    files.value.push({
      originName: file.name,
      objectKey: `bank/temp/202609/bank_0_${Date.now()}_${Math.random().toString(36).slice(2, 8)}.${file.name.split('.').pop()}`,
      ext: file.name.split('.').pop() ?? '',
      size: file.size,
      progress: 100,
      status: 'success'
    })
  })
}

function handleRemove(index: number) {
  files.value.splice(index, 1)
}

function handleBankChange(event: { detail: { value: number } }) {
  const value = Number(event.detail.value)
  selectedBankIndex.value = value === 0 ? -1 : value - 1
}

function handleSplitChange(event: { detail: { value: boolean } }) {
  splitAnswer.value = event.detail.value
}

function handleDownloadTemplate() {
  console.log('[import] template url =', fetchImportTemplateUrl('xlsx'))
  uni.showToast({ title: '模版下载开发中', icon: 'none' })
}

async function handleSubmit() {
  if (!files.value.length) {
    uni.showToast({ title: '请先上传文件', icon: 'none' })
    return
  }
  if (splitAnswer.value) {
    uni.showToast({ title: '试题与答案分开上传即将上线', icon: 'none' })
    return
  }

  const bankId = selectedBankIndex.value >= 0 ? bankStore.banks[selectedBankIndex.value].id : undefined
  const result = await createImportByUpload({
    file_id: 0,
    bank_id: bankId,
    title: files.value[0].originName,
    split_answer: splitAnswer.value
  })
  console.log('[import] biz_type =', FileBizType.BankSource, 'task =', result.task_id)
  uni.redirectTo({ url: `/pages-sub/import/task?id=${result.task_id}` })
}
</script>

<style lang="scss" scoped>
.import {
  min-height: 100vh;
  padding: $spacing-lg;
  background-color: $color-bg-page;

  &__card {
    padding: $spacing-lg;
    background-color: $color-bg-card;
    border-radius: $radius-xl;
  }

  &__title {
    display: block;
    font-size: $font-size-lg;
    font-weight: 700;
    color: $color-text-primary;
  }

  &__desc {
    display: block;
    margin: $spacing-sm 0 $spacing-lg;
    font-size: $font-size-sm;
    line-height: 1.7;
    color: $color-text-secondary;
  }

  &__options {
    margin-top: $spacing-lg;
  }

  &__option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: $spacing-md 0;
    border-bottom: 1rpx solid $color-divider;
  }

  &__option-label {
    font-size: $font-size-base;
    color: $color-text-primary;
  }

  &__option-value {
    font-size: $font-size-sm;
    color: $color-primary;
  }

  &__tips {
    margin-top: $spacing-md;
  }

  &__tip {
    display: block;
    font-size: $font-size-xs;
    line-height: 1.9;
    color: $color-text-secondary;
  }

  &__actions {
    display: flex;
    margin-top: $spacing-lg;
  }

  &__btn {
    flex: 1;
    height: 84rpx;
    border-radius: 42rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: $font-size-base;

    &--plain {
      margin-right: $spacing-sm;
      border: 2rpx solid $color-primary;
      color: $color-primary;
    }

    &--primary {
      background: $color-primary-gradient;
      color: $color-text-inverse;
      font-weight: 600;
    }
  }
}
</style>
