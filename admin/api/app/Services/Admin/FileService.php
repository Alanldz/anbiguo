<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Exceptions\BusinessException;
use App\Models\FileAsset;
use App\Support\ErrorCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 文件资源服务（docs/04 §五 API-ADM-103）
 *
 * GET 列表：分页 + 筛选（keyword=origin_name 模糊 / biz_type / user_id / storage）。
 * DELETE：软删记录（file_assets.deleted_at 置位），不调 OSS 删除接口；
 *   物理清理由 file:clean-deleted 定时任务负责。
 */
class FileService
{
    /** 列出文件资源（分页） */
    public function list(array $filters): LengthAwarePaginator
    {
        $page = (int) ($filters['page'] ?? 1);
        $pageSize = max(1, min(100, (int) ($filters['page_size'] ?? 20)));

        $query = FileAsset::query();

        if (isset($filters['keyword']) && $filters['keyword'] !== '') {
            $query->where('origin_name', 'like', '%'.$filters['keyword'].'%');
        }
        if (isset($filters['biz_type']) && $filters['biz_type'] !== '' && is_numeric($filters['biz_type'])) {
            $query->where('biz_type', (int) $filters['biz_type']);
        }
        if (isset($filters['user_id']) && $filters['user_id'] !== '' && is_numeric($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }
        if (isset($filters['storage']) && $filters['storage'] !== '') {
            $query->where('storage', $filters['storage']);
        }

        return $query->orderByDesc('id')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    /** 删除文件资源（仅软删记录，不调 OSS） */
    public function delete(int $id): void
    {
        /** @var FileAsset|null $file */
        $file = FileAsset::find($id);
        if ($file === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '文件不存在', null, 404);
        }

        // 仅置位 deleted_at；物理 OSS 对象清理由 file:clean-deleted 定时任务负责
        $file->delete();
    }

    /** 对外行结构 */
    public function toRow(FileAsset $file): array
    {
        return [
            'id'          => $file->id,
            'user_id'     => (int) $file->user_id,
            'biz_type'    => (int) $file->biz_type,
            'bank_id'     => (int) $file->bank_id,
            'category_id' => (int) $file->category_id,
            'origin_name' => $file->origin_name,
            'object_key'  => $file->object_key,
            'file_ext'    => $file->file_ext,
            'file_size'   => (int) $file->file_size,
            'mime_type'   => $file->mime_type,
            'storage'     => $file->storage,
            'is_public'   => (int) $file->is_public,
            'created_at'  => $file->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
