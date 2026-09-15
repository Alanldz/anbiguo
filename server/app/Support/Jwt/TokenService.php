<?php

declare(strict_types=1);

namespace App\Support\Jwt;

use App\Exceptions\BusinessException;
use App\Support\ErrorCode;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Support\Str;
use Throwable;

/**
 * JWT 令牌服务（docs/06-后台隔离与权限设计.md §二）
 *
 * 三端使用【三套互不相通的密钥】，Token 不能跨端使用：
 *   client  → JWT_SECRET_CLIENT   有效期 7 天（可刷新）
 *   console → JWT_SECRET_CONSOLE  有效期 2 小时（不刷新，过期重新登录）
 *   admin   → JWT_SECRET_ADMIN    有效期 30 分钟（独立应用使用，本服务不签发）
 *
 * payload 约定：
 *   iss  签发方（固定 anbiguo）
 *   sub  主体 ID（client/console 为 user_accounts.id，admin 为 sys_admins.id）
 *   scp  作用域（client / console / admin），用于防止 Token 跨界使用
 *   jti  令牌唯一 ID，用于退出登录时加入黑名单
 *   iat / nbf / exp / typ
 */
final class TokenService
{
    /** 签发令牌 */
    public static function issue(
        int $subjectId,
        string $scope,
        string $secret,
        int $ttlMinutes,
        array $extraClaims = []
    ): array {
        $now = time();
        $jti = (string) Str::uuid();

        $payload = array_merge([
            'iss' => (string) config('app.jwt_issuer', 'anbiguo'),
            'sub' => (string) $subjectId,
            'scp' => $scope,
            'jti' => $jti,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttlMinutes * 60,
        ], $extraClaims);

        $token = JWT::encode($payload, $secret, 'HS256');

        return [
            'token'      => $token,
            'jti'        => $jti,
            'expires_in' => $ttlMinutes * 60,
            'expires_at' => $now + $ttlMinutes * 60,
        ];
    }

    /**
     * 解析并校验令牌
     *
     * @return array{subject_id:int, scope:string, jti:string, payload:array}
     * @throws BusinessException 校验失败统一抛 10401，避免向攻击者暴露具体原因
     */
    public static function parse(string $token, string $secret, string $expectScope): array
    {
        try {
            $payload = (array) JWT::decode($token, new Key($secret, 'HS256'));
        } catch (ExpiredException) {
            throw new BusinessException(ErrorCode::UNAUTHORIZED, '登录已过期，请重新登录', null, 401);
        } catch (SignatureInvalidException) {
            throw new BusinessException(ErrorCode::UNAUTHORIZED, '登录凭证无效', null, 401);
        } catch (Throwable) {
            throw new BusinessException(ErrorCode::UNAUTHORIZED, '登录凭证无效', null, 401);
        }

        // 防跨端：客户端 Token 不能用于用户后台，反之亦然
        if (($payload['scp'] ?? '') !== $expectScope) {
            throw new BusinessException(ErrorCode::UNAUTHORIZED, '登录凭证与当前端不匹配', null, 401);
        }

        $subjectId = (int) ($payload['sub'] ?? 0);
        if ($subjectId <= 0) {
            throw new BusinessException(ErrorCode::UNAUTHORIZED, '登录凭证无效', null, 401);
        }

        return [
            'subject_id' => $subjectId,
            'scope'      => (string) $payload['scp'],
            'jti'        => (string) ($payload['jti'] ?? ''),
            'payload'    => $payload,
        ];
    }

    /**
     * 从 Authorization 头提取 Bearer Token
     */
    public static function extractFromHeader(?string $authorization): string
    {
        $authorization = trim((string) $authorization);

        if ($authorization === '') {
            throw new BusinessException(ErrorCode::UNAUTHORIZED, '请先登录', null, 401);
        }

        if (! preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            throw new BusinessException(ErrorCode::UNAUTHORIZED, '登录凭证格式不正确', null, 401);
        }

        return trim($matches[1]);
    }
}
