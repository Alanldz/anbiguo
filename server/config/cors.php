<?php

/**
 * 跨域配置
 *
 * 使用方：
 *   - H5 端（浏览器直连 api.xxx.com）
 *   - 用户后台前端（console.xxx.com → console-api）
 *   - 总后台前端（admin.xxx.com → /admin-api，在独立应用中配置）
 *
 * ⚠️ 微信小程序与 Android App 不受同源策略限制，无需 CORS。
 * ⚠️ 生产环境严禁使用 '*'，必须在 .env 中显式列出允许的域名。
 */
return [

    'paths' => [
        'api/*',
        'console-api/*',
        'sanctum/csrf-cookie',
        'up',
    ],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173'))
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

    // 当前使用 Bearer Token，不使用 Cookie 鉴权，故关闭凭证传递
    'supports_credentials' => false,

];
