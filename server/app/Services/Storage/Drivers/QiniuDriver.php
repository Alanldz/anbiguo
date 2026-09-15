<?php

declare(strict_types=1);

namespace App\Services\Storage\Drivers;

use App\Exceptions\BusinessException;
use App\Services\Storage\StorageDriverInterface;
use App\Support\ErrorCode;
use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use Throwable;

/**
 * 七牛云 Kodo 驱动（默认驱动，docs/05 §四）
 *
 * 配置来源：sys_configs 的 storage 分组
 *   access_key / secret_key / bucket / domain / region / token_expire / private_bucket
 *
 * 空间划分：
 *   - 公开空间：头像、公开静态资源 → CDN 直链
 *   - 私有空间：题库源文件、题目图片、学习资料、课程 → 签名 URL
 */
class QiniuDriver implements StorageDriverInterface
{
    public function __construct(
        private readonly string $accessKey,
        private readonly string $secretKey,
        private readonly string $bucket,
        private readonly string $domain,
        private readonly string $region = 'z0',
        private readonly string $privateBucket = '',
        private readonly bool $useHttps = true
    ) {
    }

    public function name(): string
    {
        return 'qiniu';
    }

    /**
     * 签发前端直传凭证
     *
     * 采用「限定到具体 key + 限定有效期」的策略，而非可写整空间的通用 token，
     * 即使凭证泄漏，攻击者也只能覆盖这一个 key，且窗口期最长 15 分钟。
     */
    public function uploadToken(string $objectKey, int $expireSeconds): string
    {
        $this->assertConfigured();

        $auth = new Auth($this->accessKey, $this->secretKey);

        $policy = [
            'scope'            => $this->scopeFor($objectKey),
            'deadline'         => time() + max($expireSeconds, 60),
            'insertOnly'       => 1,        // 同名不覆盖，避免前端误传冲掉已有文件
            'fsizeLimit'       => 0,        // 具体大小限制由业务层按 FileBizType 校验
            'returnBody'       => json_encode([
                'key'  => '$(key)',
                'hash' => '$(etag)',
                'fsize' => '$(fsize)',
                'fname' => '$(fname)',
            ], JSON_UNESCAPED_UNICODE),
        ];

        try {
            return $auth->uploadToken($this->bucket, $objectKey, $expireSeconds, $policy, true);
        } catch (Throwable $e) {
            throw new BusinessException(ErrorCode::THIRD_PARTY_ERROR, '存储凭证签发失败：'.$e->getMessage());
        }
    }

    public function put(string $objectKey, string $localPath): bool
    {
        $this->assertConfigured();

        if (! is_file($localPath)) {
            throw new BusinessException(ErrorCode::FILE_UPLOAD_FAILED, '待上传的本地文件不存在');
        }

        try {
            $token = (new Auth($this->accessKey, $this->secretKey))
                ->uploadToken($this->bucket, $objectKey, 3600, ['scope' => $this->scopeFor($objectKey)]);

            [, $err] = (new UploadManager())->putFile($token, $objectKey, $localPath);

            if ($err !== null) {
                throw new BusinessException(ErrorCode::FILE_UPLOAD_FAILED, '上传失败：'.json_encode($err, JSON_UNESCAPED_UNICODE));
            }

            return true;
        } catch (BusinessException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new BusinessException(ErrorCode::FILE_UPLOAD_FAILED, '上传失败：'.$e->getMessage());
        }
    }

    public function putContents(string $objectKey, string $contents): bool
    {
        $this->assertConfigured();

        try {
            $token = (new Auth($this->accessKey, $this->secretKey))
                ->uploadToken($this->bucket, $objectKey, 3600, ['scope' => $this->scopeFor($objectKey)]);

            [, $err] = (new UploadManager())->put($token, $objectKey, $contents);

            if ($err !== null) {
                throw new BusinessException(ErrorCode::FILE_UPLOAD_FAILED, '写入失败：'.json_encode($err, JSON_UNESCAPED_UNICODE));
            }

            return true;
        } catch (BusinessException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new BusinessException(ErrorCode::FILE_UPLOAD_FAILED, '写入失败：'.$e->getMessage());
        }
    }

    /**
     * 获取访问地址
     *
     * @param  int  $expireSeconds  0 = 公开直链；>0 = 私有签名 URL（docs/05 §四）
     */
    public function url(string $objectKey, int $expireSeconds = 0): string
    {
        $base = rtrim($this->domain, '/');
        $scheme = $this->useHttps ? 'https' : 'http';

        // 域名可能已在配置里带了协议头，做一次兼容处理
        if (str_starts_with($base, 'http://') || str_starts_with($base, 'https://')) {
            $url = $base.'/'.ltrim($objectKey, '/');
        } else {
            $url = $scheme.'://'.$base.'/'.ltrim($objectKey, '/');
        }

        if ($expireSeconds <= 0) {
            return $url;
        }

        $auth = new Auth($this->accessKey, $this->secretKey);

        return $auth->privateDownloadUrl($url, $expireSeconds);
    }

    public function delete(string $objectKey): bool
    {
        $this->assertConfigured();

        try {
            $manager = new BucketManager(
                new Auth($this->accessKey, $this->secretKey),
                new Config()
            );

            [, $err] = $manager->delete($this->bucket, $objectKey);

            // 对象本就不存在视为删除成功（幂等）
            if ($err !== null && (int) ($err->code ?? 0) !== 612) {
                return false;
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function exists(string $objectKey): bool
    {
        $this->assertConfigured();

        try {
            $manager = new BucketManager(
                new Auth($this->accessKey, $this->secretKey),
                new Config()
            );

            [$stat, $err] = $manager->stat($this->bucket, $objectKey);

            return $err === null && $stat !== null;
        } catch (Throwable) {
            return false;
        }
    }

    public function get(string $objectKey): ?string
    {
        $url = $this->url($objectKey, 600);

        $context = stream_context_create(['http' => ['timeout' => 10]]);
        $content = @file_get_contents($url, false, $context);

        return $content === false ? null : $content;
    }

    /** 私有文件写入时 scope 需指向私有空间 */
    private function scopeFor(string $objectKey): string
    {
        return $this->bucket.':'.$objectKey;
    }

    private function assertConfigured(): void
    {
        if ($this->accessKey === '' || $this->secretKey === '' || $this->bucket === '') {
            throw new BusinessException(
                ErrorCode::STORAGE_NOT_CONFIGURED,
                '七牛云存储未配置完整，请在总后台「配置中心 → 对象存储」填写 AccessKey / SecretKey / 空间名称'
            );
        }
    }
}
