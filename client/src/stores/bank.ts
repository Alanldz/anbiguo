import { defineStore } from 'pinia'
import { fetchBankDetail, fetchCategories, fetchMyBanks } from '@/api/bank'
import { getStorage, setStorage, STORAGE_KEYS } from '@/utils/storage'
import type { BankCategory, QuestionBank } from '@/types'

interface BankState {
  categories: BankCategory[]
  activeCategoryId: number
  banks: QuestionBank[]
  page: number
  hasMore: boolean
  loading: boolean
  /** 当前正在学习的题库（跨页面共享，如考试页、答题页） */
  currentBank: QuestionBank | null
}

const PAGE_SIZE = 20

export const useBankStore = defineStore('bank', {
  state: (): BankState => ({
    categories: [],
    activeCategoryId: 0,
    banks: [],
    page: 1,
    hasMore: true,
    loading: false,
    currentBank: null
  }),

  getters: {
    /** 最近学习的题库（首页"我的学习空间"展示顶部若干条） */
    recentBanks: (state) => state.banks.slice(0, 5)
  },

  actions: {
    async loadCategories() {
      if (this.categories.length) return
      this.categories = await fetchCategories()
    },

    /** 加载我的题库，reset = true 表示重载第一页 */
    async loadMyBanks(reset = false, keyword?: string) {
      if (this.loading) return
      if (reset) {
        this.page = 1
        this.hasMore = true
      }
      if (!this.hasMore) return

      this.loading = true
      try {
        const result = await fetchMyBanks({ page: this.page, page_size: PAGE_SIZE, keyword })
        this.banks = reset ? result.list : [...this.banks, ...result.list]
        this.hasMore = this.page < result.pagination.total_pages
        this.page += 1
        const first = this.banks[0]
        if (first) setStorage(STORAGE_KEYS.LAST_BANK_ID, first.id)
      } finally {
        this.loading = false
      }
    },

    async switchCategory(categoryId: number) {
      if (this.activeCategoryId === categoryId) return
      this.activeCategoryId = categoryId
      await this.loadMyBanks(true)
    },

    /** 进入题库详情前缓存当前题库，避免详情页重复请求 */
    async setCurrentBank(bankId: number) {
      const cached = this.banks.find((item) => item.id === bankId)
      if (cached) {
        this.currentBank = cached
        return cached
      }
      this.currentBank = await fetchBankDetail(bankId)
      return this.currentBank
    },

    /** 恢复上次学习的题库 ID */
    getLastBankId(): number | null {
      return getStorage<number>(STORAGE_KEYS.LAST_BANK_ID, null) ?? null
    }
  }
})
