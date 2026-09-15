<?php

declare(strict_types=1);

namespace App\Services\Console;

use App\Enums\MemberLevel;
use App\Enums\MemberStatus;
use App\Models\ExamRecord;
use App\Models\QuestionBank;
use App\Models\UserDailyStat;
use App\Models\UserFavoriteQuestion;
use App\Models\UserMember;
use App\Models\UserPracticeRecord;
use App\Models\UserProfile;
use App\Models\UserQuestionNote;
use App\Models\UserWrongQuestion;
use Illuminate\Support\Carbon;

/**
 * 学习概览服务（docs/04 §四 API-CSL-STAT-001）
 *
 * 职责：汇总当前登录用户的学习数据，并产出近 30 天趋势（缺失日期补 0，按日期升序）。
 * 所有数据以当前登录用户为边界。
 */
class ConsoleStatisticsService
{
    /**
     * 学习概览汇总
     */
    public function overview(int $userId): array
    {
        // 我的题库数 / 题目总数（冗余计数汇总）
        $bankCount = QuestionBank::where('user_id', $userId)->whereNull('deleted_at')->count();
        $questionCount = (int) QuestionBank::where('user_id', $userId)
            ->whereNull('deleted_at')->sum('question_count');

        // 累计练习 / 考试次数
        $practiceCount = UserPracticeRecord::where('user_id', $userId)->whereNull('deleted_at')->count();
        $examCount = ExamRecord::where('user_id', $userId)->count();

        // 每日统计汇总
        $agg = UserDailyStat::where('user_id', $userId)
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(answer_count),0) AS answer_count, COALESCE(SUM(right_count),0) AS right_count, COALESCE(SUM(wrong_count),0) AS wrong_count, COALESCE(SUM(duration_seconds),0) AS duration_seconds')
            ->first();

        $answerCount = (int) ($agg->answer_count ?? 0);
        $rightCount = (int) ($agg->right_count ?? 0);
        $wrongCount = (int) ($agg->wrong_count ?? 0);
        $durationSeconds = (int) ($agg->duration_seconds ?? 0);

        $correctRate = $answerCount > 0
            ? number_format($rightCount / $answerCount * 100, 2, '.', '')
            : '0.00';

        // 收藏 / 笔记 / 错题本在册数
        $profile = UserProfile::where('user_id', $userId)->whereNull('deleted_at')->first();
        $studyDays = (int) ($profile?->study_days ?? 0);

        $wrongQuestionCount = UserWrongQuestion::where('user_id', $userId)
            ->where('status', 1)->whereNull('deleted_at')->count();
        $favoriteCount = UserFavoriteQuestion::where('user_id', $userId)
            ->whereNull('deleted_at')->count();
        $noteCount = UserQuestionNote::where('user_id', $userId)
            ->whereNull('deleted_at')->count();

        return [
            'summary' => [
                'bank_count'        => $bankCount,
                'question_count'    => $questionCount,
                'practice_count'    => $practiceCount,
                'exam_count'        => $examCount,
                'answer_count'      => $answerCount,
                'right_count'       => $rightCount,
                'wrong_count'       => $wrongCount,
                'correct_rate'      => $correctRate,
                'duration_seconds'  => $durationSeconds,
                'study_days'        => $studyDays,
                'wrong_question_count' => $wrongQuestionCount,
                'favorite_count'    => $favoriteCount,
                'note_count'        => $noteCount,
            ],
            'member'  => $this->memberInfo($userId),
            'trend'   => $this->trend($userId),
        ];
    }

    /** 会员概要 */
    private function memberInfo(int $userId): array
    {
        $member = UserMember::where('user_id', $userId)->whereNull('deleted_at')->first();

        if ($member === null) {
            return [
                'level'            => MemberLevel::NORMAL->value,
                'level_text'       => MemberLevel::NORMAL->label(),
                'status'           => MemberStatus::ACTIVE->value,
                'status_text'      => MemberStatus::ACTIVE->label(),
                'expired_at'       => null,
                'ai_import_quota'  => 0,
            ];
        }

        $level = MemberLevel::tryFrom((int) $member->level) ?? MemberLevel::NORMAL;
        $status = MemberStatus::tryFrom((int) $member->status) ?? MemberStatus::ACTIVE;

        return [
            'level'           => $level->value,
            'level_text'      => $level->label(),
            'status'          => $status->value,
            'status_text'     => $status->label(),
            'expired_at'      => $member->expired_at?->toDateTimeString(),
            'ai_import_quota' => (int) $member->ai_import_quota,
        ];
    }

    /** 近 30 天趋势（含无数据日期，answer_count 补 0，按日期升序） */
    private function trend(int $userId): array
    {
        $end = Carbon::now()->startOfDay();
        $start = $end->copy()->subDays(29);

        $buckets = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $key = $date->format('Y-m-d');
            $buckets[$key] = [
                'date'            => $key,
                'answer_count'    => 0,
                'right_count'     => 0,
                'duration_seconds' => 0,
            ];
        }

        $stats = UserDailyStat::where('user_id', $userId)
            ->whereNull('deleted_at')
            ->whereBetween('stat_date', [$start->toDateString(), $end->toDateString()])
            ->get(['stat_date', 'answer_count', 'right_count', 'duration_seconds']);

        foreach ($stats as $stat) {
            $key = $stat->stat_date instanceof Carbon
                ? $stat->stat_date->format('Y-m-d')
                : (string) $stat->stat_date;
            if (! isset($buckets[$key])) {
                continue;
            }
            $buckets[$key]['answer_count'] = (int) $stat->answer_count;
            $buckets[$key]['right_count'] = (int) $stat->right_count;
            $buckets[$key]['duration_seconds'] = (int) $stat->duration_seconds;
        }

        return array_values($buckets);
    }
}
