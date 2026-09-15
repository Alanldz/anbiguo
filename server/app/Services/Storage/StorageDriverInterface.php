<?php

declare(strict_types=1);

namespace App\Services\Storage;

/**
 * 存储驱动统一接口（docs/05-OSS存储与文件分类规范.md §4.1）
 *
 * 业务代码**只依赖本接口**，不感知具体厂商。
 * 切换存储商时只需在总后台把 `storage.provider` 改成对应值，业务代码零改动。
 */
interface StorageDriverInterface
{
    /**
     * 签发前端直传凭证
     *
     * @param  string  $objectKey      目标对象 Key（须已按 docs/05 规则生成）
     * @param  int     $expireSeconds  凭证有效期（秒）
     * @return string  七牛返回 uptoken 字符串；阿里云返回 base64 策略串
     */
    public function uploadToken(string $objectKey, int $expireSeconds): string;

    /**
     * 服务端上传本地文件（用于模板、导出文件等后端生成的文件）
     */
    public function put(string $objectKey, string $localPath): bool;

    /**
     * 服务端写入字符串内容（用于生成 CSV / JSON 等小文件）
     */
    public function putContents(string $objectKey, string $contents): bool;

    /**
     * 获取访问地址
     *
     * @param  int  $expireSeconds  0 表示公开资源直链；>0 表示私有资源签名 URL
     */
    public function url(string $objectKey, int $expireSeconds = 0): string;

    /** 删除对象 */
    public function delete(string $objectKey): bool;

    /** 对象是否存在 */
    public function exists(string $objectKey): bool;

    /** 读取对象内容（小文件场景，如导入模板） */
    public function get(string $objectKey): ?string;

    /** 驱动标识：qiniu / aliyun / local */
    public function name(): string;
}
