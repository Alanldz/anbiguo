<?php

declare(strict_types=1);

use Illuminate\Support\Str;

/**
 * 数据库配置（docs/03 §一，docs/06 §二）
 *
 * ⚠️ 账号隔离：admin 应用 → app_admin，拥有全表权限（含 sys_*）。
 *   绝不在此使用 app_client / app_console 账号。
 */
return [

    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [

        'mysql' => [
            'driver'         => 'mysql',
            'url'            => env('DB_URL'),
            'host'           => env('DB_HOST', '127.0.0.1'),
            'port'           => env('DB_PORT', '3306'),
            'database'       => env('DB_DATABASE', 'anbiguo'),
            'username'       => env('DB_USERNAME', 'app_admin'),
            'password'       => env('DB_PASSWORD', ''),
            'unix_socket'    => env('DB_SOCKET', ''),
            'charset'        => env('DB_CHARSET', 'utf8mb4'),
            'collation'      => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix'         => env('DB_PREFIX', ''),
            'prefix_indexes' => true,
            'strict'         => (bool) env('DB_STRICT', true),
            'engine'         => 'InnoDB',
            'timezone'       => env('DB_TIMEZONE', '+08:00'),
            'options'        => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                PDO::ATTR_EMULATE_PREPARES => false,
            ]) : [],
        ],

    ],

    'migrations' => [
        'table'                  => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => [

        'client' => env('REDIS_CLIENT', 'predis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            // ⚠️ 必须与主应用一致：prefix 决定缓存键前缀
            'prefix'  => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'anbiguo'), '_').'_database_'),
        ],

        'default' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
