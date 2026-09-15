<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Enums\MemberLevel;
use App\Enums\WrongQuestionStatus;
use App\Models\QuestionBank;
use App\Models\User;
use App\Models\UserDailyStat;
use App\Models\UserFavoriteQuestion;
use App\Models\UserProfile;
use App\Models\UserMember;
use App\Models\UserWrongQuestion;

/**
 * 客户端用户资料与学习空间统计服务
 * 台账：docs/04-API接口规范与登记表.md §三 API-USER-001 ~ 003
 */
class UserProfileService
{
    /**
     * 获取个人资料（对齐 client/src/types UserProfile）
     */
    public function getProfile(User $user): array
    {
        $profile = $this->profileOf($user);
        $member = UserMember::where('user_id', $user->id)->whereNull('deleted_at')->first();

        return [
            'id'                 => (int) $user->id,
            'nickname'           => $profile?->nickname ?? '',
            'avatar'             => $profile?->avatar ?? '',
            'mobile'             => $user->mobile ?? '',
            'uid'                => $user->uid ?? '',
            'is_creator'         => QuestionBank::where('user_id', $user->id)->whereNull('deleted_at')->exists(),
            'member_level'       => $member !== null ? (int) $member->level : MemberLevel::NORMAL->value,
            'member_expired_at'  => $member?->expired_at?->toDateTimeString(),
        ];
    }

    /**
     * 更新个人资料（仅更新 user_profiles 可写字段）
     */
    public function updateProfile(User $user, array $data): UserProfile
    {
        $profile = $this->profileOf($user);

        if ($profile === null) {
            $profile = UserProfile::create([
                'user_id' => $user->id,
                'nickname' => (string) ($data['nickname'] ?? ''),
            ]);
        }

        $fillable = ['nickname', 'avatar', 'gender', 'birthday', 'province', 'city', 'exam_target', 'bio'];

        foreach ($fillable as $field) {
            if (array_key_exists($field, $data)) {
                $profile->{$field} = $data[$field];
            }
        }

        $profile->save();

        return $profile;
    }

    /**
     * 我的学习空间统计（对齐 client/src/types StudySummary）
     */
    public function getStudySummary(int $userId): array
    {
        $stat = UserDailyStat::where('user_id', $userId)
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(answer_count),0) as ans, COALESCE(SUM(right_count),0) as rgt, COALESCE(SUM(wrong_count),0) as wrt, COALESCE(SUM(duration_seconds),0) as dur')
            ->first();

        $answered = (int) ($stat->ans ?? 0);
        $right = (int) ($stat->rgt ?? 0);
        $duration = (int) ($stat->dur ?? 0);

        $accuracy = $answered > 0 ? round($right / $answered * 100, 1) : 0.0;

        $wrongCount = UserWrongQuestion::where('user_id', $userId)
            ->where('status', WrongQuestionStatus::IN_BOOK->value)
            ->whereNull('deleted_at')
            ->count();

        $favoriteCount = UserFavoriteQuestion::where('user_id', $userId)
            ->whereNull('deleted_at')
            ->count();

        return [
            'practice_count'  => $answered,
            'accuracy'        => $accuracy,
            'wrong_count'     => (int) $wrongCount,
            'favorite_count'  => (int) $favoriteCount,
            'study_minutes'   => (int) floor($duration / 60),
        ];
    }

    private function profileOf(User $user): ?UserProfile
    {
        return UserProfile::where('user_id', $user->id)->whereNull('deleted_at')->first();
    }
}
