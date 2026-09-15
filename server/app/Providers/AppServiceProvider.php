<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureRateLimiting();
        $this->configurePasswordRules();
    }

    /** 模型严格模式：禁止懒加载与静默丢弃字段，尽早暴露 N+1 与拼写错误 */
    private function configureModels(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Model::preventAccessingMissingAttributes(! $this->app->isProduction());
    }

    /** 数据库连接时区与严格模式（docs/03 §一） */
    private function configureDatabase(): void
    {
        // 生产环境禁止在框架层跑破坏性命令
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }

    /** 生产环境强制 HTTPS */
    private function configureUrl(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }

    /**
     * 限流规则（docs/04 §2.5）
     *
     * ⚠️ 短信类限流必须按手机号维度，不能只按 IP，否则同一 WiFi 下用户会互相挤占额度。
     */
    private function configureRateLimiting(): void
    {
        // 短信验证码：60 秒 1 次，10 次/天/手机号
        RateLimiter::for('sms-per-mobile', function (Request $request) {
            $mobile = (string) $request->input('mobile', $request->ip());

            return [
                Limit::perMinute((int) config('anbiguo.throttle.sms_per_minute', 1))->by('sms:min:'.$mobile),
                Limit::perDay((int) config('anbiguo.throttle.sms_per_day', 10))->by('sms:day:'.$mobile),
            ];
        });

        // 登录：同一手机号 10 分钟 5 次（防撞库）
        RateLimiter::for('auth-login', function (Request $request) {
            $mobile = (string) $request->input('mobile', $request->ip());

            return Limit::perMinutes(10, 5)->by('login:'.$mobile);
        });

        // 用户后台登录：同一账号 10 分钟 5 次
        RateLimiter::for('console-login', function (Request $request) {
            $mobile = (string) $request->input('mobile', $request->ip());

            return Limit::perMinutes(10, 5)->by('console-login:'.$mobile);
        });

        // 通用接口限流
        RateLimiter::for('api', function (Request $request) {
            $key = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute((int) config('anbiguo.throttle.api_per_minute', 120))->by('api:'.$key);
        });

        // AI / OCR 等重资源接口：按用户 + 会员等级配额（具体配额在 Service 层二次校验）
        RateLimiter::for('ai-heavy', function (Request $request) {
            $userId = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute(6)->by('ai:'.$userId);
        });
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
