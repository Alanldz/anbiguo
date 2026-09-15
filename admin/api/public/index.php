<?php

declare(strict_types=1);

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// 维护模式
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Composer 自动加载
require __DIR__.'/../vendor/autoload.php';

// 引导应用
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
