<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\QuestionBank;
use App\Models\QuestionImportTask;
use App\Models\SysLoginLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * 数据看板服务（docs/04 §五 API-ADM-SYS-003）
 *
 * 统计项（API-ADM-010）：
 *   user_total        用户总数
 *   user_new_today    今日新增用户
 *   bank_total        题库总数
 *   bank_pending_audit 待审核题库数（status=3）
 *   question_total    题目总数（按 bank_question_banks.question_count 冗余汇总）
 *   import_running     进行中导题任务数（status=2）
 *   login_7d          近 7 天每日登录次数 [{date,count}]
 */
class DashboardService
{
    public function summary(): array
    {
        $today = now()->startOfDay();
        $sevenDaysAgo = now()->subDays(6)->startOfDay();

        $userTotal = User::count();
        $userNewToday = User::where('created_at', '>=', $today)->count();

        $bankTotal = QuestionBank::count();
        $bankPendingAudit = QuestionBank::where('status', QuestionBank::STATUS_PENDING_AUDIT)->count();

        // 题目总数：无独立题目表计数接口时，按题库冗余计数汇总
        $questionTotal = (int) QuestionBank::query()->sum('question_count');

        $importRunning = QuestionImportTask::where('status', QuestionImportTask::STATUS_RUNNING)->count();

        $login7d = $this->loginTrend($sevenDaysAgo, $today);

        return [
            'user_total'         => $userTotal,
            'user_new_today'     => $userNewToday,
            'bank_total'         => $bankTotal,
            'bank_pending_audit' => $bankPendingAudit,
            'question_total'     => $questionTotal,
            'import_running'     => $importRunning,
            'login_7d'           => $login7d,
        ];
    }

    /** 近 7 天每日登录次数（含无登录的日期，count=0） */
    private function loginTrend($startDay, $today): array
    {
        $rows = SysLoginLog::query()
            ->where('status', SysLoginLog::RESULT_SUCCESS)
            ->where('created_at', '>=', $startDay)
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as cnt'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('cnt', 'day')
            ->all();

        $trend = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startDay->copy()->addDays($i)->toDateString();
            $trend[] = [
                'date'  => $date,
                'count' => (int) ($rows[$date] ?? 0),
            ];
        }

        return $trend;
    }
}
