<?php

declare(strict_types=1);

/**
 * 跨域配置（总后台独立域名 admin.xxx.com → /admin-api）
 *
 * ⚠️ 生产环境严禁 '*'，必须在 .env 的 CORS_ALLOWED_ORIGINS 显式列出。
 */
return [

    'paths' => [
        'admin-api/*',
        'up',
    ],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost'))
    )),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Accept',
        'Authorization',
        'X-Request-Id',
        'X-Client-Platform',
        'X-Client-Version',
        'X-Requested-With',
    ],

    'exposed_headers' => [
        'X-Request-Id',
    ],

    'max_age' => 86400,

    'supports_credentials' => false,

];
