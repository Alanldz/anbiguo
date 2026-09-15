<?php

declare(strict_types=1);

return [

    'name' => env('APP_NAME', '识途刷题总后台'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | 时区
    |--------------------------------------------------------------------------
    | 全项目统一 Asia/Shanghai，数据库连接时区 +08:00（见 docs/03 §六）。
    */
    'timezone' => env('APP_TIMEZONE', 'Asia/Shanghai'),

    'locale' => env('APP_LOCALE', 'zh_CN'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'zh_CN'),

    /*
    |--------------------------------------------------------------------------
    | 加密
    |--------------------------------------------------------------------------
    | ⚠️ APP_KEY 必须与主应用 server/.env 完全一致：
    |   sys_configs 的 is_secret=1 密文字段由 Crypt 加密，共享方能解密。
    */
    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store'  => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    // JWT 签发方标识（payload.iss），三端一致
    'jwt_issuer' => env('JWT_ISSUER', 'anbiguo'),

];
