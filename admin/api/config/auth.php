<?php

declare(strict_types=1);

/**
 * 鉴权配置（docs/06 §二）
 *
 * 总后台是【独立应用】，仅存在 admin 一个守卫：
 *   driver  = jwt（自研 AdminJwtGuard，由 AuthServiceProvider 注册）
 *   provider= sys_admins（App\Models\SysAdmin）
 *   scope   = admin（Token payload.scp 必须为 admin，否则拒绝）
 *   secret  = JWT_SECRET_ADMIN（三端独立，不得与 CLIENT/CONSOLE 共用）
 */
return [

    'defaults' => [
        'guard'     => env('AUTH_GUARD', 'admin'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [

        // 默认 web 守卫（无 Session 场景，仅占位，真正鉴权走 admin）
        'web' => [
            'driver'   => 'session',
            'provider' => 'sys_admins',
        ],

        // 总后台：/admin-api/v1/*
        'admin' => [
            'driver'   => 'jwt',
            'provider' => 'sys_admins',
            'name'     => 'admin',
            'secret'   => env('JWT_SECRET_ADMIN', ''),
            'ttl'      => (int) env('JWT_TTL_ADMIN', 30),   // 分钟 = 30 分钟
            'refresh'  => false,
        ],
    ],

    'providers' => [

        // 总后台管理员（sys_admins）
        'sys_admins' => [
            'driver' => 'eloquent',
            'model'  => App\Models\SysAdmin::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'sys_admins',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => (int) env('AUTH_PASSWORD_TIMEOUT', 10800),

];
