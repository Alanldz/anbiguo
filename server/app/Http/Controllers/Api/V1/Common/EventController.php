<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Common;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Services\Api\EventService;
use App\Support\ApiResponse;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 客户端埋点控制器
 * 台账：docs/04-API接口规范与登记表.md §三 API-EVT-001
 *
 * 只做「字面校验 → 调用 Service → ApiResponse 返回」，不写 SQL、不写业务规则。
 */
class EventController extends Controller
{
    /** 事件名单次上报条数上限 */
    private const MAX_EVENTS = 50;

    /** 事件名允许字符：字母、数字、下划线、连字符（如 app_boot / page_view） */
    private const EVENT_PATTERN = '/^[A-Za-z0-9_-]{1,64}$/';

    /** 事件发生时间格式 */
    private const DATETIME_FORMAT = 'Y-m-d H:i:s';

    public function __construct(
        private readonly EventService $service
    ) {
    }

    /** API-EVT-001 埋点批量上报 */
    public function report(Request $request): JsonResponse
    {
        $events = $request->input('events');

        // events：必填且必须为 1~50 条的索引数组
        if (! is_array($events)
            || array_values($events) !== $events
            || count($events) < 1
            || count($events) > self::MAX_EVENTS) {
            throw BusinessException::of(ErrorCode::PARAM_INVALID, 'events 需为 1~50 条的事件数组');
        }

        foreach ($events as $index => $item) {
            if (! is_array($item)) {
                throw BusinessException::of(ErrorCode::PARAM_INVALID, "events[{$index}] 不合法");
            }

            // event：必填，≤64 字，仅字母/数字/下划线/连字符
            $event = trim((string) ($item['event'] ?? ''));
            if ($event === '' || mb_strlen($event) > 64 || preg_match(self::EVENT_PATTERN, $event) !== 1) {
                throw BusinessException::of(ErrorCode::PARAM_INVALID, "events[{$index}].event 不合法");
            }

            // page：选填，≤128 字
            $page = trim((string) ($item['page'] ?? ''));
            if (mb_strlen($page) > 128) {
                throw BusinessException::of(ErrorCode::PARAM_INVALID, "events[{$index}].page 不能超过 128 字");
            }

            // biz_type：选填，≤32 字
            $bizType = trim((string) ($item['biz_type'] ?? ''));
            if (mb_strlen($bizType) > 32) {
                throw BusinessException::of(ErrorCode::PARAM_INVALID, "events[{$index}].biz_type 不能超过 32 字");
            }

            // biz_id：选填，非负整数
            $bizId = $item['biz_id'] ?? 0;
            if (! is_numeric($bizId) || (int) $bizId < 0) {
                throw BusinessException::of(ErrorCode::PARAM_INVALID, "events[{$index}].biz_id 不合法");
            }

            // extra：选填，对象且序列化后 ≤800 字
            $extra = $item['extra'] ?? [];
            if ($extra !== []) {
                if (! is_array($extra) || array_values($extra) === $extra) {
                    throw BusinessException::of(ErrorCode::PARAM_INVALID, "events[{$index}].extra 需为对象");
                }
                if (mb_strlen((string) json_encode($extra, JSON_UNESCAPED_UNICODE)) > 800) {
                    throw BusinessException::of(ErrorCode::PARAM_INVALID, "events[{$index}].extra 序列化后不能超过 800 字");
                }
            }

            // occurred_at：选填，'Y-m-d H:i:s'
            $occurredAt = $item['occurred_at'] ?? null;
            if ($occurredAt !== null) {
                $occurredAt = (string) $occurredAt;
                if (mb_strlen($occurredAt) !== 19
                    || date_create_from_format(self::DATETIME_FORMAT, $occurredAt) === false) {
                    throw BusinessException::of(ErrorCode::PARAM_INVALID, "events[{$index}].occurred_at 格式需为 Y-m-d H:i:s");
                }
                $occurredAt = date_format(date_create_from_format(self::DATETIME_FORMAT, $occurredAt), self::DATETIME_FORMAT);
            }

            $events[$index] = [
                'event' => $event,
                'page' => $page,
                'biz_type' => $bizType,
                'biz_id' => (int) $bizId,
                'extra' => $extra,
                'occurred_at' => $occurredAt,
            ];
        }

        $accepted = $this->service->report($this->currentUserId($request), $events);

        return ApiResponse::success([
            'accepted' => $accepted,
        ]);
    }
}
