<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Config\ConfigCenter;
use App\Services\Storage\StorageService;
use Illuminate\Support\ServiceProvider;

/**
 * 存储服务提供者
 *
 * 把 StorageService 注册为单例，避免一次请求内重复解析驱动与配置。
 */
class StorageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ConfigCenter::class, fn () => new ConfigCenter());

        $this->app->singleton(StorageService::class, function ($app) {
            return new StorageService($app->make(ConfigCenter::class));
        });
    }
}
