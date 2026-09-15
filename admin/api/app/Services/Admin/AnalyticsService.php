<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Analytics\SysEventLog;
use Illuminate\Support\Facades\DB;

/**
 * 埋点分析汇总服务（docs/04 §五 API-ADM-106）
 *
 * 统计项（数据源 sys_event_logs，由主应用写入）：
 *   total_events  埋点总条数
 *   total_users   上报用户数（去重 user_id > 0）
 *   today_events  今日埋点条数
 *   days          近 30 天每日 [{date, pv, uv}]（缺失日期补 0，升序）
 *   top_events    事件 Top 10 [{event, count}]
 */
class AnalyticsService
{
    /** 趋势天数 */
    private const TREND_DAYS = 30;

    /** Top 事件数 */
    private const TOP_EVENTS = 10;

    public function summary(): array
    {
        $todayStart = now()->startOfDay();
        $trendStart = now()->subDays(self::TREND_DAYS - 1)->startOfDay();

        $totalEvents = SysEventLog::count();
        $totalUsers = SysEventLog::query()->where('user_id', '>', 0)->distinct()->count('user_id');
        $todayEvents = SysEventLog::query()->where('created_at', '>=', $todayStart)->count();

        return [
            'total_events' => $totalEvents,
            'total_users' => $totalUsers,
            'today_events' => $todayEvents,
            'days' => $this->dailyTrend($trendStart),
            'top_events' => $this->topEvents(),
        ];
    }

    /** 近 30 天每日 PV/UV（缺失日期补 0，升序） */
    private function dailyTrend($startDay): array
    {
        $base = SysEventLog::query()
            ->where('created_at', '>=', $startDay);

        // 每日 PV：按天分组计数
        $pvRows = (clone $base)
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as pv'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('pv', 'day')
            ->all();

        // 每日 UV：按天分组去重 user_id（user_id=0 的未登录事件不计 UV）
        $uvRows = (clone $base)
            ->where('user_id', '>', 0)
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(DISTINCT user_id) as uv'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('uv', 'day')
            ->all();

        $days = [];
        for ($i = 0; $i < self::TREND_DAYS; $i++) {
            $date = $startDay->copy()->addDays($i)->toDateString();
            $days[] = [
                'date' => $date,
                'pv' => (int) ($pvRows[$date] ?? 0),
                'uv' => (int) ($uvRows[$date] ?? 0),
            ];
        }

        return $days;
    }

    /** 事件 Top 10（按条数降序） */
    private function topEvents(): array
    {
        return SysEventLog::query()
            ->select('event', DB::raw('COUNT(*) as count'))
            ->groupBy('event')
            ->orderByDesc('count')
            ->limit(self::TOP_EVENTS)
            ->get()
            ->map(fn ($row) => [
                'event' => (string) $row->event,
                'count' => (int) $row->count,
            ])
            ->all();
    }
}
