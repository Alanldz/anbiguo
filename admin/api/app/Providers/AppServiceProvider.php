<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

/**
 * 应用服务提供者（总后台）
 *
 * 仅保留通用配置：模型严格模式、数据库时区/严格模式、生产强制 HTTPS、密码强度。
 * 总后台不做短信/登录限流（内网 + 强密码即可）。
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureModels();
        $this->configureDatabase();
        $this->configureUrl();
        $this->configurePasswordRules();
    }

    /** 模型严格模式：尽早暴露 N+1 与拼写错误 */
    private function configureModels(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Model::preventAccessingMissingAttributes(! $this->app->isProduction());
    }

    /** 数据库连接时区与严格模式（docs/03 §一） */
    private function configureDatabase(): void
    {
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }

    /** 生产环境强制 HTTPS */
    private function configureUrl(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }

    /** 统一密码强度规则 */
    private function configurePasswordRules(): void
    {
        Password::defaults(function () {
            return $this->app->isProduction()
                ? Password::min(8)->letters()->numbers()
                : Password::min(6);
        });
    }
}
