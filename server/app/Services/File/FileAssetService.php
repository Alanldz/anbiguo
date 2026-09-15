<?php

declare(strict_types=1);

namespace App\Services\File;

use App\Enums\FileBizType;
use App\Enums\FileStatus;
use App\Exceptions\BusinessException;
use App\Models\BankCategory;
use App\Models\FileAsset;
use App\Models\FileCategory;
use App\Models\QuestionBank;
use App\Models\User;
use App\Services\Storage\StorageService;
use App\Support\ErrorCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

/**
 * 文件资源服务（docs/05-OSS存储与文件分类规范.md）
 *
 * 铁律：**所有上传文件必须先在此登记 file_assets，才允许写入 OSS**。
 *      不允许出现「OSS 有对象但数据库无记录」的孤儿文件。
 *
 * 流程：签发凭证（建待上传记录）→ 前端直传 OSS → 回调确认（置为已上传）
 */
class FileAssetService
{
    public function __construct(private readonly StorageService $storage)
    {
    }

    /**
     * 签发直传凭证（docs/05 §四 步骤①②）
     *
     * @return array{file_id:int, object_key:string, upload_token:string, ...}
     */
    public function issueUploadToken(User $user, array $data): array
    {
        $bizType = FileBizType::from((int) $data['biz_type']);
        $ext = strtolower(ltrim((string) $data['ext'], '.'));
        $size = (int) $data['size'];

        $this->assertSizeAllowed($bizType, $size);
        $this->assertBankOwnership($user, (int) ($data['bank_id'] ?? 0));
        $this->assertCategoryOwnership($user, (int) ($data['category_id'] ?? 0));

        $categoryCode = $this->resolveCategoryCode($user->id, (int) ($data['category_id'] ?? 0));

        // 先生成 ticket（内部会做扩展名白名单校验并抛出明确错误）
        $ticket = $this->storage->makeUploadTicket(
            $bizType,
            $ext,
            (int) $user->id,
            (int) ($data['bank_id'] ?? 0),
            (int) ($data['question_id'] ?? 0),
            (int) ($data['category_id'] ?? 0),
            $categoryCode
        );

        $asset = FileAsset::create([
            'user_id'     => $user->id,
            'biz_type'    => $bizType->value,
            'bank_id'     => (int) ($data['bank_id'] ?? 0),
            'category_id' => (int) ($data['category_id'] ?? 0),
            'question_id' => (int) ($data['question_id'] ?? 0),
            'origin_name' => (string) ($data['origin_name'] ?? ''),
            'object_key'  => $ticket['object_key'],
            'file_ext'    => $ext,
            'file_size'   => $size,
            'file_hash'   => '',
            'mime_type'   => '',
            'storage'     => $ticket['driver'],
            'is_public'   => $bizType->isPublicReadable() ? 1 : 0,
            'status'      => FileStatus::PENDING_UPLOAD->value,
            'ref_count'   => 0,
            // 临时文件设置过期时间，由 file:clean-temp 定时任务清理
            'expired_at'  => $bizType === FileBizType::TEMP
                ? now()->addDays((int) config('anbiguo.file.temp_expire_days', 7))
                : null,
        ]);

        return [
            'file_id'      => $asset->id,
            'object_key'   => $ticket['object_key'],
            'upload_token' => $ticket['upload_token'],
            'upload_url'   => $ticket['upload_url'],
            'domain'       => $ticket['domain'],
            'expires_in'   => $ticket['expires_in'],
            'driver'       => $ticket['driver'],
            'max_size'     => $bizType->maxSizeInBytes(),
        ];
    }

    /**
     * 上传完成回调（docs/05 §四 步骤④）
     *
     * 只允许登记「本人发起且已存在待上传记录」的对象，防止他人伪造 object_key。
     */
    public function complete(User $user, array $data): FileAsset
    {
        $objectKey = (string) $data['object_key'];

        $asset = FileAsset::query()
            ->where('object_key', $objectKey)
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->first();

        if ($asset === null) {
            throw new BusinessException(ErrorCode::FILE_NOT_FOUND, '未找到对应的上传记录，请重新发起上传');
        }

        if ((int) $asset->status === FileStatus::UPLOADED->value) {
            return $asset;   // 幂等：重复回调直接返回
        }

        // 校验对象确实已存在于 OSS，避免前端跳过上传直接回调
        if (! $this->storage->exists($objectKey)) {
            throw new BusinessException(ErrorCode::FILE_UPLOAD_FAILED, '文件尚未上传成功，请重试');
        }

        $asset->status = FileStatus::UPLOADED->value;

        if (! empty($data['file_hash'])) {
            $asset->file_hash = (string) $data['file_hash'];
        }
        if (isset($data['file_size'])) {
            $asset->file_size = (int) $data['file_size'];
        }
        if (! empty($data['origin_name'])) {
            $asset->origin_name = (string) $data['origin_name'];
        }
        if (isset($data['bank_id'])) {
            $asset->bank_id = (int) $data['bank_id'];
        }
        if (isset($data['category_id'])) {
            $asset->category_id = (int) $data['category_id'];
        }

        $asset->save();

        // 直属分类文件数 +1（冗余计数）
        if ((int) $asset->category_id > 0) {
            FileCategory::whereKey($asset->category_id)->update([
                'file_count' => DB::raw('file_count + 1'),
                'updated_at' => now(),
            ]);
        }

        return $asset;
    }

    /**
     * 学习资料列表
     */
    public function paginateAssets(User $user, array $filters, int $page, int $pageSize): LengthAwarePaginator
    {
        $query = FileAsset::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->whereIn('status', [FileStatus::UPLOADED->value, FileStatus::ARCHIVED->value]);

        if (! empty($filters['biz_type'])) {
            $query->where('biz_type', (int) $filters['biz_type']);
        }

        if (! empty($filters['bank_id'])) {
            $query->where('bank_id', (int) $filters['bank_id']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['keyword'])) {
            $keyword = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim((string) $filters['keyword']));
            $query->where('origin_name', 'like', "%{$keyword}%");
        }

        return $query->orderByDesc('id')->paginate($pageSize, [
            'id', 'biz_type', 'bank_id', 'category_id', 'origin_name', 'object_key',
            'file_ext', 'file_size', 'storage', 'is_public', 'status', 'created_at',
        ], 'page', $page);
    }

    /**
     * 生成私有文件签名下载地址
     */
    public function signedUrl(User $user, FileAsset $asset, int $expireSeconds = 900): string
    {
        if ((int) $asset->user_id !== (int) $user->id && (int) $asset->is_public !== 1) {
            throw new BusinessException(ErrorCode::NO_PERMISSION, '无权访问该文件');
        }

        if ($asset->expired_at !== null && $asset->expired_at->isPast()) {
            throw new BusinessException(ErrorCode::FILE_EXPIRED);
        }

        $bizType = FileBizType::tryFrom((int) $asset->biz_type) ?? FileBizType::TEMP;

        return $this->storage->urlFor($bizType, (string) $asset->object_key, $expireSeconds);
    }

    /** 大小上限校验（docs/05 §三） */
    private function assertSizeAllowed(FileBizType $bizType, int $size): void
    {
        $max = $bizType->maxSizeInBytes();

        if ($max > 0 && $size > $max) {
            throw new BusinessException(
                ErrorCode::FILE_TOO_LARGE,
                sprintf('「%s」单个文件不超过 %s', $bizType->label(), $this->humanSize($max))
            );
        }
    }

    /** 题库归属校验：只能往自己的题库上传 */
    private function assertBankOwnership(User $user, int $bankId): void
    {
        if ($bankId <= 0) {
            return;
        }

        $owned = QuestionBank::whereKey($bankId)
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->exists();

        if (! $owned) {
            throw new BusinessException(ErrorCode::BANK_NO_PERMISSION, '无权向该题库上传文件');
        }
    }

    /** 分类归属校验（系统预置分类 user_id=0 允许所有人使用） */
    private function assertCategoryOwnership(User $user, int $categoryId): void
    {
        if ($categoryId <= 0) {
            return;
        }

        $allowed = FileCategory::whereKey($categoryId)
            ->whereIn('user_id', [0, $user->id])
            ->whereNull('deleted_at')
            ->exists();

        if (! $allowed) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '文件分类不存在');
        }
    }

    /** 取分类编码，用于 OSS 目录命名 */
    private function resolveCategoryCode(int $userId, int $categoryId): string
    {
        if ($categoryId <= 0) {
            return 'uncategorized';
        }

        $code = FileCategory::whereKey($categoryId)
            ->whereIn('user_id', [0, $userId])
            ->value('code');

        return (string) ($code ?: 'uncategorized');
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 1).'GB';
        }

        if ($bytes >= 1048576) {
            return round($bytes / 1048576).'MB';
        }

        return round($bytes / 1024).'KB';
    }
}
