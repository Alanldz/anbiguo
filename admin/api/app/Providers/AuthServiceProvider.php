<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\AdminJwtGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

/**
 * 鉴权服务提供者
 *
 * 注册 JWT 守卫驱动 'jwt' → AdminJwtGuard（作用域固定 admin）。
 * config/auth.php 的 guards.admin 使用 driver=jwt 即可命中本实现。
 */
class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Auth::extend('jwt', function ($app, $name, array $config) {
            return new AdminJwtGuard(
                Auth::createUserProvider($config['provider']),
                $app['request'],
                $config + ['name' => $name]
            );
        });
    }
}
