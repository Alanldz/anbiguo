<?php

declare(strict_types=1);

namespace App\Auth;

use App\Support\Jwt\TokenService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

/**
 * 三端通用 JWT 守卫（docs/06 §二）
 *
 * 由 config/auth.php 中的 guards.client / guards.console 声明，
 * 通过 AuthServiceProvider 注册为 auth 驱动的 'jwt' 扩展。
 *
 * 每个守卫自带：
 *   - secret：独立签名密钥（三端不同，保证 Token 不可跨端复用）
 *   - scope ：作用域标识（client / console），与 payload.scp 双向校验
 *   - ttl   ：有效期（分钟）
 */
class JwtGuard implements Guard
{
    private ?Authenticatable $user = null;

    private bool $resolved = false;

    public function __construct(
        private readonly UserProvider $provider,
        private readonly Request $request,
        private readonly array $config = []
    ) {
    }

    /** 当前登录用户；未登录返回 null，不抛异常 */
    public function user(): ?Authenticatable
    {
        if ($this->resolved) {
            return $this->user;
        }

        $this->resolved = true;
        $this->user = $this->resolveUser();

        return $this->user;
    }

    /** 是否已登录 */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /** 是否为游客 */
    public function guest(): bool
    {
        return ! $this->check();
    }

    /** 当前用户 ID */
    public function id(): int|string|null
    {
        return $this->user()?->getAuthIdentifier();
    }

    /** 是否已有已解析的用户实例 */
    public function hasUser(): bool
    {
        return $this->user !== null;
    }

    /** 手动注入用户（登录成功后调用，便于后续同一请求内读取） */
    public function setUser(Authenticatable $user): static
    {
        $this->user = $user;
        $this->resolved = true;

        return $this;
    }

    /** 凭证有效性校验（供 attempt 等场景使用） */
    public function validate(array $credentials = []): bool
    {
        return $this->user() !== null;
    }

    /**
     * 强制鉴权：未登录直接抛 AuthenticationException
     * 由框架的 auth 中间件调用，最终被 bootstrap/app.php 转成 10401
     */
    public function authenticate(): Authenticatable
    {
        $user = $this->user();

        if ($user === null) {
            throw new AuthenticationException('Unauthenticated.', [$this->config['name'] ?? 'client']);
        }

        return $user;
    }

    /** 解析 Token 并加载用户；任何失败都返回 null，由 authenticate() 统一抛异常 */
    private function resolveUser(): ?Authenticatable
    {
        $secret = (string) ($this->config['secret'] ?? '');
        $scope = (string) ($this->config['name'] ?? '');

        if ($secret === '') {
            // 密钥未配置属于部署事故：宁可拒绝请求，也不要放行
            return null;
        }

        $token = $this->request->bearerToken();
        if (! is_string($token) || trim($token) === '') {
            return null;
        }

        try {
            $parsed = TokenService::parse(trim($token), $secret, $scope);
        } catch (\Throwable) {
            return null;
        }

        // 退出登录后的令牌加入黑名单（jti 级）
        if ($parsed['jti'] !== '' && TokenBlacklist::has($parsed['jti'])) {
            return null;
        }

        $user = $this->provider->retrieveById($parsed['subject_id']);

        return $user instanceof Authenticatable ? $user : null;
    }
}
