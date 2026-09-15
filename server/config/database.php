<?php

use Illuminate\Support\Str;

/**
 * 数据库配置（docs/03-数据库设计规范.md）
 *
 * ⚠️ 账号隔离（docs/06 §二）：
 *   api 站点     → app_client   无 sys_ 表权限，无 DDL 权限
 *   console 站点 → app_console  同上
 *   admin 应用   → app_admin    拥有全表权限（在 admin/api/.env 中配置）
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
            'username'       => env('DB_USERNAME', 'app_client'),
            'password'       => env('DB_PASSWORD', ''),
            'unix_socket'    => env('DB_SOCKET', ''),
            'charset'        => env('DB_CHARSET', 'utf8mb4'),
            'collation'      => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix'         => env('DB_PREFIX', ''),
            'prefix_indexes' => true,
            'strict'         => (bool) env('DB_STRICT', true),
            'engine'         => 'InnoDB',
            // 连接时区：与应用时区保持一致，避免 created_at 出现 8 小时偏差
            'timezone'       => env('DB_TIMEZONE', '+08:00'),
            'options'        => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                PDO::ATTR_EMULATE_PREPARES => false,   // 使用真实预处理，防 SQL 注入并提升性能
            ]) : [],
        ],

        // 迁移专用连接：使用有 DDL 权限的账号（仅部署时用）
        'mysql_migrate' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => env('DB_DATABASE', 'anbiguo'),
            'username'  => env('DB_MIGRATE_USERNAME', env('DB_USERNAME')),
            'password'  => env('DB_MIGRATE_PASSWORD', env('DB_PASSWORD')),
            'charset'   => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix'    => '',
            'strict'    => true,
            'engine'    => 'InnoDB',
            'timezone'  => env('DB_TIMEZONE', '+08:00'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | 迁移仓库表
    |--------------------------------------------------------------------------
    */
    'migrations' => [
        'table'                  => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis
    |--------------------------------------------------------------------------
    */
    'redis' => [

        'client' => env('REDIS_CLIENT', 'predis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
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
