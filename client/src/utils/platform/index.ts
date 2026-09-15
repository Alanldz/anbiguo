/**
 * 平台差异能力抽象层
 * 规则：登录、支付、分享、扫码等平台差异能力统一在此封装，
 *       业务层只调用统一方法，禁止在页面里散落 #ifdef 判断。
 * 详见 docs/01-技术架构与选型.md「多端兼容策略」。
 */

/** 当前运行平台标识，与接口头 X-Client-Platform 对应 */
export type ClientPlatform = 'mp-weixin' | 'app-android' | 'app-ios' | 'h5'

export function getClientPlatform(): ClientPlatform {
  // #ifdef MP-WEIXIN
  return 'mp-weixin'
  // #endif
  // #ifdef APP-PLUS
  const sys = uni.getSystemInfoSync()
  return sys.platform === 'ios' ? 'app-ios' : 'app-android'
  // #endif
  // #ifdef H5
  return 'h5'
  // #endif
  // eslint-disable-next-line no-unreachable
  return 'h5'
}

/** 客户端版本号，与接口头 X-Client-Version 对应 */
export function getClientVersion(): string {
  // #ifdef MP-WEIXIN
  return uni.getAccountInfoSync?.().miniProgram?.version ?? '1.0.0'
  // #endif
  // #ifdef APP-PLUS
  return plus.runtime.version
  // #endif
  // #ifdef H5
  return '1.0.0'
  // #endif
  // eslint-disable-next-line no-unreachable
  return '1.0.0'
}

/** 获取状态栏高度（自定义导航栏使用） */
export function getStatusBarHeight(): number {
  return uni.getSystemInfoSync().statusBarHeight ?? 0
}

/** 平台相关的登录凭证获取：小程序返回 code，其他端返回 null */
export function getPlatformLoginCode(): Promise<string | null> {
  return new Promise((resolve) => {
    // #ifdef MP-WEIXIN
    uni.login({
      provider: 'weixin',
      success: (res) => resolve(res.code),
      fail: () => resolve(null)
    })
    // #endif
    // #ifndef MP-WEIXIN
    resolve(null)
    // #endif
  })
}

/** 统一分享能力（各端行为不同，业务层无需关心） */
export function shareContent(options: { title: string; path: string; imageUrl?: string }): void {
  // #ifdef MP-WEIXIN
  uni.showShareMenu({ withShareTicket: true })
  // #endif
  // #ifdef APP-PLUS
  uni.share({
    provider: 'weixin',
    scene: 'WXSceneSession',
    type: 5,
    href: options.path,
    title: options.title,
    imageUrl: options.imageUrl,
    success: () => uni.showToast({ title: '分享成功', icon: 'none' }),
    fail: () => uni.showToast({ title: '分享已取消', icon: 'none' })
  })
  // #endif
  // #ifdef H5
  uni.showToast({ title: '请复制链接分享', icon: 'none' })
  // #endif
}
