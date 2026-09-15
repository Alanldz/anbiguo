<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Auth\TokenBlacklist;
use App\Enums\RegisterSource;
use App\Enums\UserStatus;
use App\Exceptions\BusinessException;
use App\Models\User;
use App\Models\UserMember;
use App\Models\UserProfile;
use App\Services\Sms\SmsService;
use App\Services\Wechat\WechatMiniProgramService;
use App\Support\ErrorCode;
use App\Support\Jwt\TokenService;
use Illuminate\Support\Facades\DB;

/**
 * 客户端认证服务（docs/04 §三 API-AUTH-*）
 *
 * 覆盖：短信验证码 → 登录/注册 → 签发 Token → 刷新 → 退出
 * 用户后台认证见 App\Services\Console\ConsoleAuthService（独立密钥、独立时长）
 */
class AuthService
{
    public function __construct(
        private readonly SmsService $smsService,
        private readonly WechatMiniProgramService $wechatService,
        private readonly UserRegisterService $registerService
    ) {
    }

    // =========================================================================
    // API-AUTH-001 发送短信验证码
    // =========================================================================
    public function sendSmsCode(string $mobile): array
    {
        return $this->smsService->sendCode($mobile, 'login');
    }

    // =========================================================================
    // API-AUTH-002 手机号登录 / 注册
    // =========================================================================
    public function loginByMobile(string $mobile, string $code): array
    {
        // 未注册手机号允许先发码再注册：先校验码，再按需建号
        $this->smsService->verifyCode($mobile, $code, 'login');

        $user = $this->registerService->createByMobile($mobile, RegisterSource::MOBILE);

        $this->assertUserUsable($user);

        return $this->loginResult($user);
    }

    // =========================================================================
    // API-AUTH-003 微信小程序登录
    // =========================================================================
    public function loginByWechat(string $code): array
    {
        $session = $this->wechatService->code2Session($code);

        $user = User::where('wx_mp_openid', $session['openid'])->first();

        if ($user === null) {
            // 首次微信登录：尚无手机号，用占位手机号建号，后续在「绑定手机号」时补全
            $user = $this->registerService->createByMobile(
                $this->placeholderMobile(),
                RegisterSource::WECHAT_MP,
                ['openid' => $session['openid'], 'unionid' => $session['unionid']]
            );
        } elseif ($session['unionid'] !== '' && $user->wx_unionid !== $session['unionid']) {
            $this->registerService->bindWechat($user, $session['openid'], $session['unionid']);
        }

        $this->assertUserUsable($user);

        return $this->loginResult($user);
    }

    // =========================================================================
    // API-AUTH-004 刷新 Token
    // =========================================================================
    public function refresh(User $user): array
    {
        $this->assertUserUsable($user);

        return $this->loginResult($user);
    }

    // =========================================================================
    // API-AUTH-005 退出登录
    // =========================================================================
    public function logout(string $jti, int $expiresIn = 604800): void
    {
        TokenBlacklist::add($jti, $expiresIn);
    }

    /**
     * 更新登录足迹（登录成功后调用）
     */
    public function touchLogin(User $user, string $ip): void
    {
        DB::table('user_accounts')->where('id', $user->id)->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
            'updated_at'    => now(),
        ]);
    }

    /**
     * 组装登录返回（含 Token、用户概要、会员状态）
     */
    private function loginResult(User $user): array
    {
        $secret = (string) config('auth.guards.client.secret', '');
        if ($secret === '') {
            throw new BusinessException(ErrorCode::SERVER_ERROR, 'JWT_SECRET_CLIENT 未配置，请检查服务端环境变量');
        }

        $ttl = (int) config('auth.guards.client.ttl', 10080);

        $token = TokenService::issue($user->id, 'client', $secret, $ttl);

        $profile = UserProfile::where('user_id', $user->id)->first();
        $member  = UserMember::where('user_id', $user->id)->first();

        return [
            'token'      => $token['token'],
            'expires_in' => $token['expires_in'],
            'user'       => [
                'id'       => $user->id,
                'uid'      => $user->uid,
                'mobile'   => $this->maskMobile($user->mobile),
                'nickname' => $profile?->nickname ?? '',
                'avatar'   => $profile?->avatar ?? '',
                'is_new'   => $user->wasRecentlyCreated,
            ],
            'member'     => [
                'level'      => (int) ($member?->level ?? 0),
                'status'     => (int) ($member?->status ?? 1),
                'expired_at' => $member?->expired_at?->toDateTimeString(),
            ],
        ];
    }

    private function assertUserUsable(User $user): void
    {
        if ((int) $user->status === UserStatus::DISABLED->value) {
            throw new BusinessException(ErrorCode::ACCOUNT_DISABLED, '', null, 403);
        }

        if (in_array((int) $user->status, [UserStatus::CANCELING->value, UserStatus::CANCELED->value], true)) {
            throw new BusinessException(ErrorCode::UNAUTHORIZED, '该账号已注销');
        }
    }

    /**
     * 微信首登占位手机号
     *
     * 约定：占位号格式 `wx` + 11 位随机数字（共 13 位，不占用真实号段）。
     *      用户名下若已绑定真实手机号，会覆盖此占位值。
     */
    private function placeholderMobile(): string
    {
        do {
            $mobile = 'wx'.random_int(10000000000, 99999999999);
        } while (User::withTrashed()->where('mobile', $mobile)->exists());

        return $mobile;
    }

    private function maskMobile(string $mobile): string
    {
        return strlen($mobile) >= 11
            ? substr($mobile, 0, 3).'****'.substr($mobile, -4)
            : $mobile;
    }
}
