<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Services\Api\FeedbackService;
use App\Support\ApiResponse;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 客户端意见反馈控制器
 * 台账：docs/04-API接口规范与登记表.md §二 API-FBK-001
 *
 * 只做「字面校验 → 调用 Service → ApiResponse 返回」，不写 SQL、不写业务规则。
 */
class FeedbackController extends Controller
{
    /** 反馈类型合法值：1=功能异常 2=体验建议 3=其他 */
    private const TYPES = [1, 2, 3];

    public function __construct(
        private readonly FeedbackService $service
    ) {
    }

    /** API-FBK-001 提交意见反馈 */
    public function store(Request $request): JsonResponse
    {
        $type = $request->input('type');
        $content = trim((string) $request->input('content', ''));
        $images = $request->input('images');
        $contact = trim((string) $request->input('contact', ''));

        // type：必填且必须为 1/2/3
        if (! is_numeric($type) || ! in_array((int) $type, self::TYPES, true)) {
            throw BusinessException::of(ErrorCode::PARAM_INVALID, '反馈类型不合法');
        }

        // content：必填 5~500 字
        $contentLength = mb_strlen($content);
        if ($contentLength < 5 || $contentLength > 500) {
            throw BusinessException::of(ErrorCode::PARAM_INVALID, '反馈内容需为 5~500 字');
        }

        // images：可选，file_assets id 数组，最多 9 张
        if ($images !== null) {
            if (! is_array($images)
                || count($images) > 9
                || array_values($images) !== $images
                || array_filter($images, fn ($v) => ! is_numeric($v) || (int) $v < 1) !== []) {
                throw BusinessException::of(ErrorCode::PARAM_INVALID, '反馈截图不合法');
            }
        }

        // contact：选填，≤64 字
        if (mb_strlen($contact) > 64) {
            throw BusinessException::of(ErrorCode::PARAM_INVALID, '联系方式不能超过 64 字');
        }

        $this->service->submit(
            $this->currentUserId($request),
            (int) $type,
            $content,
            $images === null ? [] : array_map(intval(...), $images),
            $contact
        );

        return ApiResponse::success([
            'submitted' => true,
            'message' => '感谢反馈，我们会尽快处理',
        ]);
    }
}
