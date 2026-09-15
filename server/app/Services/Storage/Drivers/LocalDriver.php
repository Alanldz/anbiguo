<?php

declare(strict_types=1);

namespace App\Services\Storage\Drivers;

use App\Exceptions\BusinessException;
use App\Services\Storage\StorageDriverInterface;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\Storage;

/**
 * 本地磁盘驱动（仅用于本地开发与联调）
 *
 * ⚠️ 生产环境禁止使用：本地磁盘不具备弹性扩容与 CDN 能力，
 *    且与「文件统一登记到 file_assets」的运维假设不符。
 *    切换方式：总后台配置中心把 storage.provider 改为 qiniu。
 */
class LocalDriver implements StorageDriverInterface
{
    public function __construct(
        private readonly string $root = 'private',
        private readonly string $publicRoot = 'public',
        private readonly bool $publicReadable = false
    ) {
    }

    public function name(): string
    {
        return 'local';
    }

    /**
     * 本地驱动没有「前端直传」概念，
     * 返回一个带签名的一次性上传凭证，前端改走服务端中转接口 /api/v1/files/upload。
     */
    public function uploadToken(string $objectKey, int $expireSeconds): string
    {
        return base64_encode(json_encode([
            'driver'     => 'local',
            'key'        => $objectKey,
            'expires_at' => time() + $expireSeconds,
            'sign'       => hash_hmac('sha256', $objectKey.'|'.(time() + $expireSeconds), (string) config('app.key')),
        ], JSON_UNESCAPED_UNICODE));
    }

    public function put(string $objectKey, string $localPath): bool
    {
        if (! is_file($localPath)) {
            throw new BusinessException(ErrorCode::FILE_UPLOAD_FAILED, '待上传的本地文件不存在');
        }

        $stream = fopen($localPath, 'rb');
        if ($stream === false) {
            throw new BusinessException(ErrorCode::FILE_UPLOAD_FAILED, '本地文件不可读');
        }

        $ok = Storage::disk('local')->put($this->root.'/'.$objectKey, $stream);
        fclose($stream);

        return (bool) $ok;
    }

    public function putContents(string $objectKey, string $contents): bool
    {
        return (bool) Storage::disk('local')->put($this->root.'/'.$objectKey, $contents);
    }

    public function url(string $objectKey, int $expireSeconds = 0): string
    {
        // 本地环境直接给可访问路径，不做签名（仅在 APP_DEBUG=true 时可用）
        if (! config('app.debug')) {
            throw new BusinessException(ErrorCode::STORAGE_NOT_CONFIGURED, '本地存储驱动不可用于生产环境');
        }

        return url('/storage-local/'.ltrim($objectKey, '/'));
    }

    public function delete(string $objectKey): bool
    {
        return (bool) Storage::disk('local')->delete($this->root.'/'.$objectKey);
    }

    public function exists(string $objectKey): bool
    {
        return Storage::disk('local')->exists($this->root.'/'.$objectKey);
    }

    public function get(string $objectKey): ?string
    {
        return Storage::disk('local')->get($this->root.'/'.$objectKey);
    }
}
