<?php

declare(strict_types=1);

namespace App\Services\Console;

use App\Auth\TokenBlacklist;
use App\Enums\MemberLevel;
use App\Enums\UserStatus;
use App\Exceptions\BusinessException;
use App\Models\User;
use App\Models\UserMember;
use App\Models\UserProfile;
use App\Services\Sms\SmsService;
use App\Support\ErrorCode;
use App\Support\Jwt\TokenService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * 用户后台认证服务（docs/04 §四 API-CSL-AUTH-*）
 *
 * 与客户端的关键差异（docs/06 §二）：
 *   1. 独立 JWT 密钥（JWT_SECRET_CONSOLE），与客户端 Token 互不通用
 *   2. 有效期 2 小时，不提供刷新接口，过期必须重新登录
 *   3. 账号体系与客户端相同（同一张 user_accounts），但登录入口与审计独立
 *
 * 登录方式：
 *   - 手机号 + 密码（推荐，电脑端输入体验更好）
 *   - 手机号 + 短信验证码（兜底，适用于尚未设置密码的用户）
 */
class ConsoleAuthService
{
    /** 登录场景标识，与客户端登录的验证码隔离，避免互相顶掉 */
    public const SMS_SCENE = 'console';

    /** 换绑手机场景标识 */
    public const SMS_SCENE_BIND = 'bind';

    public function __construct(private readonly SmsService $smsService)
    {
    }

    /**
     * 发送用户后台短信验证码
     *
     * @param  string  $scene  login=登录（默认）/ bind=换绑手机
     */
    public function sendSmsCode(string $mobile, string $scene = self::SMS_SCENE): array
    {
        $user = User::where('mobile', $mobile)->first();

        // 不暴露「该手机号是否已注册」，统一返回成功提示（防账号枚举）
        if ($user === null) {
            return ['debug_code' => null];
        }

        return $this->smsService->sendCode($mobile, $scene);
    }

    /**
     * 校验短信验证码（一次性，成功后立即销毁）
     */
    public function verifySmsCode(string $mobile, string $code, string $scene = self::SMS_SCENE): void
    {
        $this->smsService->verifyCode($mobile, $code, $scene);
    }

    /**
     * 密码登录
     */
    public function loginByPassword(string $mobile, string $password): array
    {
        $user = User::where('mobile', $mobile)->first();

        if ($user === null) {
            // 统一错误提示，不区分「账号不存在」与「密码错误」
            throw new BusinessException(ErrorCode::ADMIN_LOGIN_FAILED, '手机号或密码错误', null, 401);
        }

        if ($user->password === '' || ! Hash::check($password, $user->password)) {
            throw new BusinessException(ErrorCode::ADMIN_LOGIN_FAILED, '手机号或密码错误', null, 401);
        }

        $this->assertUsable($user);

        return $this->issueConsoleToken($user);
    }

    /**
     * 验证码登录
     */
    public function loginByCode(string $mobile, string $code): array
    {
        $user = User::where('mobile', $mobile)->first();

        if ($user === null) {
            throw new BusinessException(ErrorCode::ACCOUNT_NOT_FOUND, '该手机号尚未注册', null, 404);
        }

        $this->smsService->verifyCode($mobile, $code, self::SMS_SCENE);
        $this->assertUsable($user);

        return $this->issueConsoleToken($user);
    }

    /**
     * 当前登录用户信息与可用信息（API-CSL-AUTH-003）
     */
    public function me(User $user): array
    {
        $profile = UserProfile::where('user_id', $user->id)->first();
        $member = UserMember::where('user_id', $user->id)->first();

        $level = MemberLevel::tryFrom((int) ($member?->level ?? MemberLevel::NORMAL->value)) ?? MemberLevel::NORMAL;

        return [
            'id'       => $user->id,
            'uid'      => $user->uid,
            'mobile'   => $user->mobile,
            'nickname' => $profile?->nickname ?? '',
            'avatar'   => $profile?->avatar ?? '',
            'member'   => [
                'level'      => $level->value,
                'level_text' => $level->label(),
                'expired_at' => $member?->expired_at?->toDateTimeString(),
            ],
        ];
    }

    /**
     * 退出登录
     */
    public function logout(string $jti, int $expiresIn = 7200): void
    {
        TokenBlacklist::add($jti, $expiresIn);
    }

    /** 签发用户后台 Token */
    private function issueConsoleToken(User $user): array
    {
        $secret = (string) config('auth.guards.console.secret', '');
        if ($secret === '') {
            throw new BusinessException(ErrorCode::SERVER_ERROR, 'JWT_SECRET_CONSOLE 未配置，请检查服务端环境变量');
        }

        $ttl = (int) config('auth.guards.console.ttl', 120);
        $token = TokenService::issue($user->id, 'console', $secret, $ttl);

        DB::table('user_accounts')->where('id', $user->id)->update([
            'last_login_at' => now(),
            'updated_at'    => now(),
        ]);

        return [
            'token'      => $token['token'],
            'expires_in' => $token['expires_in'],
            'user'       => $this->me($user),
        ];
    }

    private function assertUsable(User $user): void
    {
        if ((int) $user->status === UserStatus::DISABLED->value) {
            throw new BusinessException(ErrorCode::ACCOUNT_DISABLED, '', null, 403);
        }

        if (in_array((int) $user->status, [UserStatus::CANCELING->value, UserStatus::CANCELED->value], true)) {
            throw new BusinessException(ErrorCode::ACCOUNT_NOT_FOUND, '该账号已注销', null, 404);
        }
    }
}
