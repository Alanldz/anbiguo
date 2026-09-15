<?php

declare(strict_types=1);

namespace App\Services\Storage;

use App\Enums\FileBizType;
use App\Exceptions\BusinessException;
use App\Services\Config\ConfigCenter;
use App\Services\Storage\Drivers\LocalDriver;
use App\Services\Storage\Drivers\QiniuDriver;
use App\Support\ErrorCode;

/**
 * 存储服务门面（docs/04 §六 存储抽象层约定、docs/05 §4.1）
 *
 * 业务代码**只注入本类**，不直接 new 任何驱动。
 * 厂商由 `sys_configs.storage.provider` 决定，切换存储商无需改业务代码。
 *
 * 用法：
 *   $objectKey = ObjectKeyGenerator::make(FileBizType::BANK_SOURCE, 'xlsx', userId: 7, bankId: 1024);
 *   $token = $storage->uploadToken($objectKey);
 *   $url   = $storage->url($objectKey, 900);
 */
class StorageService
{
    private ?StorageDriverInterface $driver = null;

    /** 驱动实例缓存（单请求内只解析一次） */
    private static ?StorageDriverInterface $sharedDriver = null;

    public function __construct(private readonly ConfigCenter $config)
    {
    }

    /** 当前驱动 */
    public function driver(): StorageDriverInterface
    {
        if ($this->driver !== null) {
            return $this->driver;
        }

        if (self::$sharedDriver !== null) {
            return $this->driver = self::$sharedDriver;
        }

        $provider = (string) $this->config->get('storage.provider', config('anbiguo.storage.provider', 'local'));

        $this->driver = self::$sharedDriver = match ($provider) {
            'qiniu' => new QiniuDriver(
                accessKey: (string) $this->config->get('storage.access_key', ''),
                secretKey: (string) $this->config->get('storage.secret_key', ''),
                bucket: (string) $this->config->get('storage.bucket', ''),
                domain: (string) $this->config->get('storage.domain', ''),
                region: (string) $this->config->get('storage.region', 'z0'),
                privateBucket: (string) $this->config->get('storage.private_bucket', ''),
            ),
            'local' => new LocalDriver((string) config('anbiguo.storage.local_root', 'private')),
            default => throw new BusinessException(
                ErrorCode::STORAGE_NOT_CONFIGURED,
                "存储驱动 {$provider} 尚未实现，请在总后台配置中心改为 qiniu 或 local"
            ),
        };

        return $this->driver;
    }

    /** 签发前端直传凭证（docs/05 §四 步骤②） */
    public function uploadToken(string $objectKey, ?int $expireSeconds = null): string
    {
        $expire = $expireSeconds ?? (int) $this->config->get('storage.token_expire', 900);

        return $this->driver()->uploadToken($objectKey, $expire);
    }

    /** 生成对象 Key + 直传凭证，一步返回给前端 */
    public function makeUploadTicket(
        FileBizType $bizType,
        string $ext,
        int $userId,
        int $bankId = 0,
        int $questionId = 0,
        int $categoryId = 0,
        string $categoryCode = ''
    ): array {
        $objectKey = ObjectKeyGenerator::make($bizType, $ext, $userId, $bankId, $questionId, $categoryId, $categoryCode);

        $expire = (int) $this->config->get('storage.token_expire', 900);

        return [
            'object_key'   => $objectKey,
            'upload_token' => $this->uploadToken($objectKey, $expire),
            'upload_url'   => $this->driver()->name() === 'qiniu' ? 'https://upload.qiniup.com' : '',
            'domain'       => rtrim((string) $this->config->get('storage.domain', ''), '/'),
            'expires_in'   => $expire,
            'driver'       => $this->driver()->name(),
        ];
    }

    /** 服务端上传本地文件 */
    public function put(string $objectKey, string $localPath): bool
    {
        return $this->driver()->put($objectKey, $localPath);
    }

    /** 服务端写入内容 */
    public function putContents(string $objectKey, string $contents): bool
    {
        return $this->driver()->putContents($objectKey, $contents);
    }

    /**
     * 获取访问地址
     *
     * @param  int  $expireSeconds  0 = 直链（仅用于公开资源）；私有资源必须传有效期
     */
    public function url(string $objectKey, int $expireSeconds = 0): string
    {
        return $this->driver()->url($objectKey, $expireSeconds);
    }

    /** 按业务类型自动决定是否需要签名（docs/05 §三「是否公开读」列） */
    public function urlFor(FileBizType $bizType, string $objectKey, int $expireSeconds = 900): string
    {
        return $bizType->isPublicReadable()
            ? $this->driver()->url($objectKey, 0)
            : $this->driver()->url($objectKey, $expireSeconds);
    }

    public function delete(string $objectKey): bool
    {
        return $this->driver()->delete($objectKey);
    }

    public function exists(string $objectKey): bool
    {
        return $this->driver()->exists($objectKey);
    }

    public function get(string $objectKey): ?string
    {
        return $this->driver()->get($objectKey);
    }

    /** 当前驱动是否已具备可用配置（用于总后台「测试连通性」） */
    public function isConfigured(): bool
    {
        return $this->config->isConfigured('storage', ['provider', 'access_key', 'secret_key', 'bucket', 'domain']);
    }
}
