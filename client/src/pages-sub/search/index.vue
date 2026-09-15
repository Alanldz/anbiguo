<template>
  <base-dev-placeholder
    page-id="P-22"
    title="搜索 / 搜题"
    desc="支持题库内搜索试题、拍照搜题、文字搜题三种方式，快速定位题目与解析。"
    :features="features"
  />
</template>

<script setup lang="ts">
/**
 * 搜索 / 搜题（P-22）
 * 接口：API-SRC-001 题库内搜索试题、API-SRC-002 拍照/文字搜题、API-IMP-005 OCR
 * 入口参数：mode = bank（题库内）| photo（拍照）| text（文字）
 */
const features = [
  '搜索历史与热门搜索词（本地缓存 key: SEARCH_HISTORY）',
  '文字搜题：输入题干关键词匹配题库与解析',
  '拍照搜题：拍照或相册选图，OCR 识别后匹配',
  '题库内搜索：限定当前题库范围检索',
  '搜索结果展示题干、答案、解析，可直接收藏'
]
</script>
