<?php

/**
 * 鉴权配置（docs/06-后台隔离与权限设计.md §二）
 *
 * 关键机制：三端使用【三套不同密钥 + 不同作用域】，Token 无法跨端使用。
 *   client  → 客户端（小程序 / Android / H5），7 天有效，支持刷新
 *   console → 用户电脑端后台，2 小时有效，不刷新，过期重新登录
 *   admin   → 总后台（独立应用 admin/api/ 使用，本应用不签发）
 *
 * 守卫驱动 'jwt' 由 App\Providers\AuthServiceProvider 注册。
 */

return [

    'defaults' => [
        'guard'     => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [

        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],

        // ---------------------------------------------------------------------
        // 客户端：/api/v1/*
        // ---------------------------------------------------------------------
        'client' => [
            'driver'   => 'jwt',
            'provider' => 'users',
            'name'     => 'client',
            'secret'   => env('JWT_SECRET_CLIENT', ''),
            'ttl'      => (int) env('JWT_TTL_CLIENT', 10080),   // 分钟 = 7 天
            'refresh'  => true,
        ],

        // ---------------------------------------------------------------------
        // 用户后台：/console-api/v1/*
        // ---------------------------------------------------------------------
        'console' => [
            'driver'   => 'jwt',
            'provider' => 'console_users',
            'name'     => 'console',
            'secret'   => env('JWT_SECRET_CONSOLE', ''),
            'ttl'      => (int) env('JWT_TTL_CONSOLE', 120),     // 分钟 = 2 小时
            'refresh'  => false,
        ],

        // ---------------------------------------------------------------------
        // 总后台：/admin-api/v1/*（独立应用使用，此处保留定义便于本地联调）
        // ---------------------------------------------------------------------
        'admin' => [
            'driver'   => 'jwt',
            'provider' => 'admins',
            'name'     => 'admin',
            'secret'   => env('JWT_SECRET_ADMIN', ''),
            'ttl'      => (int) env('JWT_TTL_ADMIN', 30),        // 分钟 = 30 分钟
            'refresh'  => false,
        ],
    ],

    'providers' => [

        // 客户端用户（user_accounts）
        'users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\User::class,
        ],

        // 用户后台用户：与客户端同一张表，但走独立守卫与独立密钥
        'console_users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\User::class,
        ],

        // 总后台管理员（sys_admins）
        'admins' => [
            'driver' => 'eloquent',
            'model'  => App\Models\SysAdmin::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    // 密码确认页面的超时时间（秒）
    'password_timeout' => (int) env('AUTH_PASSWORD_TIMEOUT', 10800),

];
