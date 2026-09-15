<?php

return [

    'name' => env('APP_NAME', '安必果刷题'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | 时区
    |--------------------------------------------------------------------------
    | 全项目统一 Asia/Shanghai，数据库连接时区 +08:00（见 docs/03 §六）。
    | 严禁在业务代码里用 date_default_timezone_set 二次修改。
    */
    'timezone' => env('APP_TIMEZONE', 'Asia/Shanghai'),

    'locale' => env('APP_LOCALE', 'zh_CN'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'zh_CN'),

    /*
    |--------------------------------------------------------------------------
    | 加密
    |--------------------------------------------------------------------------
    | 用于 Cookie / Session / 配置中心（sys_configs 密文字段）加解密。
    | ⚠️ APP_KEY 一旦启用不可更换，否则已加密的第三方密钥将无法解密。
    */
    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | 维护模式
    |--------------------------------------------------------------------------
    */
    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store'  => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 项目自定义
    |--------------------------------------------------------------------------
    */

    /** JWT 签发方标识（payload.iss），三端一致，用于快速识别非法来源令牌 */
    'jwt_issuer' => env('JWT_ISSUER', 'anbiguo'),

];
