<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\JwtGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

/**
 * 鉴权服务提供者
 *
 * 注册 JWT 守卫驱动，供 config/auth.php 中的 client / console / admin 三个守卫使用。
 * 三端各自持有独立密钥（secret）与作用域（name），从机制上保证 Token 不可跨端复用。
 */
class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Auth::extend('jwt', function ($app, $name, array $config) {
            return new JwtGuard(
                Auth::createUserProvider($config['provider']),
                $app['request'],
                $config + ['name' => $name]
            );
        });
    }
}
