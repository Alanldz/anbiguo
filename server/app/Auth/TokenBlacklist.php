<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Support\Facades\Cache;

/**
 * 令牌黑名单
 *
 * 用途：用户「退出登录」后立即失效该 Token，而不必等到自然过期。
 * 实现：以 jti 为键写入缓存（Redis），TTL 取 Token 剩余有效期，自动清理。
 *
 * 设计取舍：JWT 是无状态的，做黑名单会引入一次缓存查询。
 *           本项目规模下 Redis 查询成本可忽略，换取「退出即失效」的确定性体验。
 */
final class TokenBlacklist
{
    private const KEY_PREFIX = 'jwt:blacklist:';

    /** 将令牌加入黑名单 */
    public static function add(string $jti, int $expiresInSeconds): void
    {
        if ($jti === '') {
            return;
        }

        $ttl = max($expiresInSeconds, 60);

        Cache::put(self::KEY_PREFIX.$jti, 1, $ttl);
    }

    /** 是否已失效 */
    public static function has(string $jti): bool
    {
        if ($jti === '') {
            return false;
        }

        return Cache::has(self::KEY_PREFIX.$jti);
    }
}
