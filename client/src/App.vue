<script setup lang="ts">
import { onLaunch, onShow, onError } from '@dcloudio/uni-app'
import { useUserStore } from '@/stores/user'

onLaunch(() => {
  // 启动时恢复登录态并拉取一次用户信息
  const userStore = useUserStore()
  userStore.restore()
  userStore.fetchProfileIfLogged()
  console.log('[App] launch, platform =', uni.getSystemInfoSync().uniPlatform)
})

onShow(() => {
  // 预留：从后台切回时刷新会员状态
})

onError((err) => {
  console.error('[App] error:', err)
})
</script>

<style lang="scss">
@import '@/styles/common.scss';

page {
  background-color: $color-bg-page;
  color: $color-text-primary;
  font-size: $font-size-base;
  font-family: -apple-system, BlinkMacSystemFont, 'PingFang SC', 'Helvetica Neue', Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
}
</style>
