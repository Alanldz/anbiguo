<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\FileAsset;
use App\Models\SysFeedback;
use App\Support\ErrorCode;
use App\Exceptions\BusinessException;

/**
 * 客户端意见反馈服务
 * 台账：docs/04-API接口规范与登记表.md §二 API-FBK-001
 *
 * 仅负责落库（sys_feedbacks），参数字面校验在控制器层完成；
 * 处理流程（status/reply/handler_id）由总后台 API-ADM-104 负责。
 */
class FeedbackService
{
    /**
     * 提交意见反馈
     *
     * @param  int  $userId  当前登录用户 ID（sys_feedbacks.user_id）
     * @param  int  $type  反馈类型 1=功能异常 2=体验建议 3=其他
     * @param  string  $content  反馈内容（5~500 字，控制器已校验）
     * @param  int[]  $images  截图 file_assets id 数组（≤9 张，控制器已校验）
     * @param  string  $contact  联系方式（≤64 字，选填）
     *
     * @throws BusinessException 图片资产不存在时抛 PARAM_INVALID
     */
    public function submit(int $userId, int $type, string $content, array $images, string $contact): void
    {
        // 截图归属校验：仅允许引用本人上传且未删除的文件资产，避免脏数据
        if ($images !== []) {
            $exists = FileAsset::query()
                ->whereIn('id', array_values(array_unique($images)))
                ->where('user_id', $userId)
                ->count();
            if ($exists < count(array_unique($images))) {
                throw BusinessException::of(ErrorCode::PARAM_INVALID, '反馈截图不存在或无权引用');
            }
        }

        SysFeedback::create([
            'user_id' => $userId,
            'type' => $type,
            'content' => $content,
            'images_json' => $images,
            'contact' => $contact,
            'status' => 0,
        ]);
    }
}
