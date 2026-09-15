<?php

declare(strict_types=1);

namespace App\Services\Console;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Console\ConsoleAuthService;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\Hash;

/**
 * 账号设置服务（docs/04 §四 API-CSL-ACC-*）
 *
 * 规则：所有操作绑定当前登录用户；换绑手机需校验 bind 场景短信验证码。
 */
class ConsoleAccountService
{
    public function __construct(private readonly ConsoleAuthService $authService)
    {
    }

    /**
     * 个人资料
     */
    public function profile(int $userId): array
    {
        /** @var User $user */
        $user = User::whereKey($userId)->whereNull('deleted_at')->firstOrFail();
        $profile = UserProfile::where('user_id', $userId)->whereNull('deleted_at')->first();

        return [
            'id'           => $user->id,
            'uid'          => $user->uid,
            'mobile'       => $user->mobile,
            'nickname'     => $profile?->nickname ?? '',
            'avatar'       => $profile?->avatar ?? '',
            'gender'       => (int) ($profile?->gender ?? 0),
            'birthday'     => $profile?->birthday?->toDateString(),
            'province'     => $profile?->province ?? '',
            'city'         => $profile?->city ?? '',
            'exam_target'  => $profile?->exam_target ?? '',
            'bio'          => $profile?->bio ?? '',
            'study_days'   => (int) ($profile?->study_days ?? 0),
            'study_seconds' => (int) ($profile?->study_seconds ?? 0),
            'created_at'   => $user->created_at?->toDateTimeString(),
        ];
    }

    /**
     * 更新资料
     */
    public function updateProfile(int $userId, array $data): array
    {
        $profile = UserProfile::where('user_id', $userId)->whereNull('deleted_at')->first();

        if ($profile === null) {
            $profile = UserProfile::create(['user_id' => $userId]);
        }

        $fillable = ['nickname', 'avatar', 'gender', 'birthday', 'province', 'city', 'exam_target', 'bio'];
        foreach ($fillable as $field) {
            if (array_key_exists($field, $data)) {
                $value = $data[$field];
                if ($field === 'birthday' && ($value === '' || $value === null)) {
                    $profile->birthday = null;
                } else {
                    $profile->{$field} = $value;
                }
            }
        }
        $profile->save();

        return $this->profile($userId);
    }

    /**
     * 修改密码（账号无密码时 old_password 可空）
     */
    public function changePassword(int $userId, ?string $oldPassword, string $newPassword): void
    {
        /** @var User $user */
        $user = User::whereKey($userId)->whereNull('deleted_at')->firstOrFail();

        if ($user->password !== '' && $user->password !== null) {
            if ($oldPassword === null || ! Hash::check($oldPassword, $user->password)) {
                throw new BusinessException(ErrorCode::PASSWORD_ERROR);
            }
        }

        $user->password = $newPassword; // 模型 hashed _cast 自动散列
        $user->save();
    }

    /**
     * 换绑手机（校验 bind 场景验证码）
     */
    public function changeMobile(int $userId, string $newMobile, string $code): void
    {
        $exists = User::where('mobile', $newMobile)
            ->where('id', '<>', $userId)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            throw new BusinessException(ErrorCode::MOBILE_REGISTERED, '该手机号已被其他账号使用');
        }

        $this->authService->verifySmsCode($newMobile, $code, ConsoleAuthService::SMS_SCENE_BIND);

        /** @var User $user */
        $user = User::whereKey($userId)->whereNull('deleted_at')->firstOrFail();
        $user->mobile = $newMobile;
        $user->save();
    }
}
