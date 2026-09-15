<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

// 控制台命令注册（当前总后台无自定义命令，保持空壳）。
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('显示一句鼓励的话');
