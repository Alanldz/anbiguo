<?php

declare(strict_types=1);

use Illuminate\Support\Str;

return [

    'default' => env('CACHE_STORE', 'redis'),

    'stores' => [

        'array' => [
            'driver'    => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver'          => 'database',
            'connection'      => env('DB_CACHE_CONNECTION'),
            'table'           => env('DB_CACHE_TABLE', 'cache'),
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
            'lock_table'      => env('DB_CACHE_LOCK_TABLE'),
        ],

        'file' => [
            'driver' => 'file',
            'path'   => storage_path('framework/cache/data'),
        ],

        'redis' => [
            'driver'           => 'redis',
            'connection'       => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection'  => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | 缓存前缀
    |--------------------------------------------------------------------------
    | ⚠️ 必须与主应用 server/.env 的 CACHE_PREFIX 完全一致（默认 anbiguo_cache）。
    |   原因：配置缓存键 sys_config:group:{group} 跨应用共享，
    |   总后台改配置后 Cache::forget 才能让主应用立即失效（API-ADM-041）。
    */
    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'anbiguo'), '_').'_cache_'),

];
