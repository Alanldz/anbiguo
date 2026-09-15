<?php

declare(strict_types=1);

namespace App\Services\Console;

use App\Enums\FileBizType;
use App\Enums\FileStatus;
use App\Exceptions\BusinessException;
use App\Models\FileAsset;
use App\Models\FileCategory;
use App\Services\File\FileAssetService;
use App\Support\ErrorCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 学习资料服务（docs/04 §四 API-CSL-FIL-*）
 *
 * 规则：资料分类与文件资源均以当前登录用户为边界（系统预置分类 user_id=0 可混用）。
 *       上传直传凭证复用 App\Services\File\FileAssetService 的逻辑，不重写。
 */
class ConsoleFileService
{
    public function __construct(private readonly FileAssetService $fileService)
    {
    }

    /**
     * 资料分类列表（系统分类 user_id=0 + 本人自建分类）
     */
    public function categories(int $userId): array
    {
        return FileCategory::query()
            ->whereIn('user_id', [0, $userId])
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'parent_id', 'name', 'code', 'file_count', 'sort_order'])
            ->map(fn (FileCategory $c) => [
                'id'         => $c->id,
                'parent_id'  => (int) $c->parent_id,
                'name'       => $c->name,
                'code'       => $c->code,
                'file_count' => (int) $c->file_count,
                'sort_order' => (int) $c->sort_order,
            ])
            ->all();
    }

    /**
     * 新建资料分类（code 由后端按名称生成，同用户内唯一）
     */
    public function createCategory(int $userId, array $data): array
    {
        $code = $this->genCode($userId, (string) $data['name']);

        $category = FileCategory::create([
            'user_id'     => $userId,
            'parent_id'   => (int) ($data['parent_id'] ?? 0),
            'name'        => (string) $data['name'],
            'code'       => $code,
            'file_count'  => 0,
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
            'status'      => 1,
        ]);

        return ['id' => $category->id];
    }

    /**
     * 更新资料分类（仅本人自建，系统预置分类不可改）
     */
    public function updateCategory(int $categoryId, int $userId, array $data): void
    {
        $category = $this->assertOwned($categoryId, $userId);

        if (isset($data['name'])) {
            $category->name = (string) $data['name'];
        }
        if (isset($data['sort_order'])) {
            $category->sort_order = (int) $data['sort_order'];
        }
        $category->save();
    }

    /**
     * 删除资料分类（仅本人自建）
     */
    public function deleteCategory(int $categoryId, int $userId): void
    {
        $category = $this->assertOwned($categoryId, $userId);
        $category->delete();
    }

    /**
     * 资料列表
     */
    public function paginateAssets(int $userId, array $filters, int $page, int $pageSize): LengthAwarePaginator
    {
        $query = FileAsset::with('category:id,name')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->whereIn('status', [FileStatus::UPLOADED->value, FileStatus::ARCHIVED->value]);

        if (! empty($filters['keyword'])) {
            $keyword = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim((string) $filters['keyword']));
            $query->where('origin_name', 'like', "%{$keyword}%");
        }
        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }
        if (! empty($filters['biz_type'])) {
            $query->where('biz_type', (int) $filters['biz_type']);
        }

        return $query->orderByDesc('id')
            ->paginate($pageSize, ['*'], 'page', $page)
            ->through(fn (FileAsset $a) => $this->toItem($a));
    }

    /**
     * 上传后登记入库
     */
    public function registerUploadedFile(int $userId, array $data): array
    {
        $asset = FileAsset::where('object_key', (string) $data['object_key'])
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if ($asset === null) {
            throw new BusinessException(ErrorCode::FILE_NOT_FOUND, '未找到对应的上传记录，请重新发起上传');
        }

        $oldCategoryId = (int) $asset->category_id;

        DB::transaction(function () use ($asset, $data, $oldCategoryId) {
            if (isset($data['origin_name'])) {
                $asset->origin_name = (string) $data['origin_name'];
            }
            if (isset($data['file_ext'])) {
                $asset->file_ext = strtolower(ltrim((string) $data['file_ext'], '.'));
            }
            if (isset($data['file_size'])) {
                $asset->file_size = (int) $data['file_size'];
            }
            if (isset($data['file_hash'])) {
                $asset->file_hash = (string) $data['file_hash'];
            }
            if (isset($data['mime_type'])) {
                $asset->mime_type = (string) $data['mime_type'];
            }
            if (isset($data['category_id'])) {
                $asset->category_id = (int) $data['category_id'];
            }
            if (isset($data['biz_type'])) {
                $asset->biz_type = (int) $data['biz_type'];
            }
            if (isset($data['is_public'])) {
                $asset->is_public = (int) $data['is_public'] > 0 ? 1 : 0;
            }
            $oldStatus = (int) $asset->getOriginal('status');
            $asset->status = FileStatus::UPLOADED->value;
            $asset->save();

            $newCategoryId = (int) $asset->category_id;
            if ($newCategoryId !== $oldCategoryId) {
                if ($oldCategoryId > 0) {
                    FileCategory::whereKey($oldCategoryId)
                        ->whereRaw('file_count > 0')
                        ->update(['file_count' => DB::raw('GREATEST(file_count - 1, 0)'), 'updated_at' => now()]);
                }
                if ($newCategoryId > 0) {
                    FileCategory::whereKey($newCategoryId)->increment('file_count');
                }
            } elseif ($newCategoryId > 0 && $oldStatus !== FileStatus::UPLOADED->value) {
                FileCategory::whereKey($newCategoryId)->increment('file_count');
            }
        });

        return $this->toItem($asset->fresh(['category']));
    }

    /**
     * 删除资料（软删）
     */
    public function deleteAsset(int $assetId, int $userId): void
    {
        $asset = FileAsset::whereKey($assetId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if ($asset === null) {
            throw new BusinessException(ErrorCode::FILE_NOT_FOUND);
        }

        DB::transaction(function () use ($asset) {
            $categoryId = (int) $asset->category_id;
            $asset->delete();
            if ($categoryId > 0) {
                FileCategory::whereKey($categoryId)
                    ->whereRaw('file_count > 0')
                    ->update(['file_count' => DB::raw('GREATEST(file_count - 1, 0)'), 'updated_at' => now()]);
            }
        });
    }

    /**
     * 获取下载地址
     */
    public function url(\App\Models\User $user, int $assetId): array
    {
        $asset = FileAsset::whereKey($assetId)
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->first();

        if ($asset === null) {
            throw new BusinessException(ErrorCode::FILE_NOT_FOUND);
        }

        $url = $this->fileService->signedUrl($user, $asset, 3600);

        return ['url' => $url, 'expires_in' => 3600];
    }

    /**
     * 上传直传凭证（复用 FileAssetService 逻辑）
     */
    public function uploadToken(\App\Models\User $user, array $data): array
    {
        $mapped = [
            'biz_type'    => (int) $data['biz_type'],
            'ext'         => strtolower(ltrim((string) ($data['file_ext'] ?? ''), '.')),
            'size'        => 0,
            'origin_name' => (string) ($data['file_name'] ?? ''),
            'bank_id'     => 0,
            'question_id' => 0,
            'category_id' => (int) ($data['category_id'] ?? 0),
        ];

        $result = $this->fileService->issueUploadToken($user, $mapped);

        return [
            'provider'     => $result['driver'] ?? 'qiniu',
            'upload_token' => $result['upload_token'],
            'upload_url'   => $result['upload_url'],
            'object_key'   => $result['object_key'],
            'expires_in'   => $result['expires_in'],
            'max_size'     => $result['max_size'],
        ];
    }

    /** FileAssetItem 输出 */
    public function toItem(FileAsset $a): array
    {
        return [
            'id'             => $a->id,
            'category_id'    => (int) $a->category_id,
            'category_name'  => $a->category?->name ?? '',
            'biz_type'       => (int) $a->biz_type,
            'biz_type_text'  => FileBizType::tryFrom((int) $a->biz_type)?->label() ?? '',
            'origin_name'    => $a->origin_name,
            'file_ext'       => $a->file_ext,
            'file_size'      => (int) $a->file_size,
            'file_size_text' => $this->humanSize((int) $a->file_size),
            'mime_type'      => $a->mime_type,
            'is_public'      => (int) $a->is_public,
            'status'         => (int) $a->status,
            'status_text'    => FileStatus::tryFrom((int) $a->status)?->label() ?? '',
            'created_at'     => $a->created_at?->toDateTimeString(),
        ];
    }

    /** 分类归属校验（仅本人自建） */
    private function assertOwned(int $categoryId, int $userId): FileCategory
    {
        $category = FileCategory::whereKey($categoryId)->whereNull('deleted_at')->first();

        if ($category === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '分类不存在');
        }
        if ((int) $category->user_id !== $userId) {
            throw new BusinessException(ErrorCode::FORBIDDEN, '无权操作该分类');
        }

        return $category;
    }

    /** 按名称生成分类编码（同用户内唯一） */
    private function genCode(int $userId, string $name): string
    {
        $base = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', $name), '_'));
        if ($base === '') {
            $base = 'cat_'.substr(md5($name), 0, 8);
        }

        $code = $base;
        $i = 1;
        while (FileCategory::where('user_id', $userId)->where('code', $code)->whereNull('deleted_at')->exists()) {
            $code = $base.'_'.$i++;
        }

        return $code;
    }

    /** 文件大小可读化 */
    private function humanSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    }
}
