<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\SysEventLog;
use App\Support\RequestContext;

/**
 * 客户端埋点服务
 * 台账：docs/04-API接口规范与登记表.md §三 API-EVT-001
 *
 * 仅负责落库（sys_event_logs），参数字面校验在控制器层完成；
 * 埋点为高吞吐 append-only 场景，写入不做事务包裹，批量插入用一条 insert SQL。
 */
class EventService
{
    /** 单条埋点扩展 JSON 最大长度（超出说明控制器校验被绕过，截断兜底） */
    private const EXTRA_MAX_LENGTH = 800;

    /**
     * 批量上报埋点
     *
     * @param  int  $userId  当前登录用户 ID（0=未登录，本期客户端仅登录后上报）
     * @param  array<int, array{event: string, page?: string, biz_type?: string, biz_id?: int, extra?: array, occurred_at?: string}>  $events  控制器已校验的事件列表（1~50 条）
     * @return int 实际插入条数
     */
    public function report(int $userId, array $events): int
    {
        if ($events === []) {
            return 0;
        }

        $now = now();
        $platform = RequestContext::platform();
        $rows = [];

        foreach ($events as $item) {
            $extra = $item['extra'] ?? [];
            $rows[] = [
                'user_id' => $userId,
                'event' => $item['event'],
                'page' => $item['page'] ?? '',
                'biz_type' => $item['biz_type'] ?? '',
                'biz_id' => (int) ($item['biz_id'] ?? 0),
                'extra_json' => $extra === []
                    ? ''
                    : mb_substr(json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '', 0, self::EXTRA_MAX_LENGTH),
                'client_platform' => $platform,
                'occurred_at' => $item['occurred_at'] ?? $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // 一条 insert SQL 批量写入（不做事务包裹，逐条插入可接受的 append-only 场景）
        SysEventLog::query()->insert($rows);

        return count($rows);
    }
}
