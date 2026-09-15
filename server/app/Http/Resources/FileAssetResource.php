<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\FileBizType;
use App\Enums\FileStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 文件资源出参
 *
 * ⚠️ 注意：不返回 object_key 之外的存储凭据，也不返回 storage 账号信息；
 *          私有文件的访问地址一律通过 /api/v1/files/assets/{id}/url 动态签发。
 *
 * @mixin \App\Models\FileAsset
 */
class FileAssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $bizType = FileBizType::tryFrom((int) $this->biz_type);
        $status = FileStatus::tryFrom((int) $this->status);

        return [
            'id'           => $this->id,
            'biz_type'     => (int) $this->biz_type,
            'biz_type_text' => $bizType?->label() ?? '',
            'bank_id'      => (int) $this->bank_id,
            'category_id'  => (int) $this->category_id,
            'question_id'  => (int) $this->question_id,
            'origin_name'  => $this->origin_name,
            'file_ext'     => $this->file_ext,
            'file_size'    => (int) $this->file_size,
            'file_size_text' => $this->humanSize((int) $this->file_size),
            'is_public'    => (bool) $this->is_public,
            'status'       => (int) $this->status,
            'status_text'  => $status?->label() ?? '',
            'expired_at'   => $this->expired_at?->toDateTimeString(),
            'created_at'   => $this->created_at?->toDateTimeString(),
        ];
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 1).'GB';
        }

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).'MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024).'KB';
        }

        return $bytes.'B';
    }
}
