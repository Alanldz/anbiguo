<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Enums\MemberLevel;
use App\Enums\MemberStatus;
use App\Enums\RegisterSource;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\UserMember;
use App\Models\UserProfile;
use Illuminate\Support\Facades\DB;

/**
 * 用户注册服务
 *
 * 职责：创建账号主体 + 同步创建资料与会员记录，保证三表一致。
 * 约定：一切涉及「账号 + 资料 + 会员」的创建都必须走本服务，禁止在别处零散 insert。
 */
class UserRegisterService
{
    /**
     * 创建用户（幂等：手机号已存在则直接返回）
     *
     * @param  array{openid?:string, unionid?:string}  $wechat
     */
    public function createByMobile(string $mobile, RegisterSource $source, array $wechat = []): User
    {
        $existing = User::withTrashed()->where('mobile', $mobile)->first();

        if ($existing !== null) {
            // 已注销账号重新注册：恢复账号并重置基础信息
            if ($existing->trashed() || (int) $existing->status === UserStatus::CANCELED->value) {
                return $this->restoreAccount($existing, $source, $wechat);
            }

            return $existing;
        }

        return DB::transaction(function () use ($mobile, $source, $wechat) {
            $user = User::create([
                'uid'                => $this->generateUid(),
                'mobile'             => $mobile,
                'mobile_country_code' => '86',
                'wx_mp_openid'       => $wechat['openid'] ?? '',
                'wx_unionid'         => $wechat['unionid'] ?? '',
                'register_source'    => $source->value,
                'status'             => UserStatus::NORMAL->value,
            ]);

            $this->initRelatedRecords($user);

            return $user;
        });
    }

    /**
     * 微信登录时补全手机号：把 openid 绑定到已有手机号账号，或创建新账号
     */
    public function bindWechat(User $user, string $openid, string $unionid = ''): User
    {
        $user->wx_mp_openid = $openid;

        if ($unionid !== '') {
            $user->wx_unionid = $unionid;
        }

        $user->save();

        return $user;
    }

    /** 初始化资料与会员记录 */
    public function initRelatedRecords(User $user): void
    {
        $now = now();

        UserProfile::create([
            'user_id'  => $user->id,
            'nickname' => '学员'.substr((string) $user->uid, -6),
            'avatar'   => '',
            'gender'   => 0,
        ]);

        UserMember::create([
            'user_id'          => $user->id,
            'level'            => MemberLevel::NORMAL->value,
            'status'           => MemberStatus::ACTIVE->value,
            'started_at'       => $now,
            'expired_at'       => null,
            'source_type'      => 1,
            'ai_import_quota'  => 0,
        ]);
    }

    /**
     * 生成全局唯一 UID
     *
     * 规则：8 位数字，首位非 0。采用「随机 + 冲突重试」而非自增，
     *      避免通过 UID 直接推断平台注册量与注册顺序。
     */
    public function generateUid(): string
    {
        for ($i = 0; $i < 10; $i++) {
            $uid = (string) random_int(10000000, 99999999);

            if (! User::withTrashed()->where('uid', $uid)->exists()) {
                return $uid;
            }
        }

        // 极端冲突兜底：时间戳后 8 位 + 随机位，保证仍然可读
        return substr((string) (time().random_int(100, 999)), -8);
    }

    /** 恢复已注销账号 */
    private function restoreAccount(User $user, RegisterSource $source, array $wechat): User
    {
        return DB::transaction(function () use ($user, $source, $wechat) {
            $user->restore();
            $user->status = UserStatus::NORMAL->value;
            $user->register_source = $source->value;

            if (! empty($wechat['openid'])) {
                $user->wx_mp_openid = $wechat['openid'];
            }
            if (! empty($wechat['unionid'])) {
                $user->wx_unionid = $wechat['unionid'];
            }

            $user->save();

            if (! UserProfile::where('user_id', $user->id)->exists()) {
                $this->initRelatedRecords($user);
            }

            return $user->refresh();
        });
    }
}
