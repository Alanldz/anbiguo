<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/**
 * 控制台命令与定时任务
 * 新增命令后必须在 docs/07-项目管理与协作规范.md 中登记
 */

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// -----------------------------------------------------------------------------
// 定时任务（服务器 crontab 只需一条：* * * * * php /path/artisan schedule:run）
// -----------------------------------------------------------------------------

// 会员过期扫描：每天 00:30 将到期会员置为已过期
Schedule::command('member:expire-scan')->dailyAt('00:30');

// 未支付订单自动关闭：每 10 分钟
Schedule::command('order:close-expired')->everyTenMinutes();

// 临时文件清理：每天 03:00 清理 7 天前到期的 temp 文件
Schedule::command('file:clean-temp')->dailyAt('03:00');

// 软删除文件清理：每天 03:30 清理已软删 30 天的文件与 OSS 对象
Schedule::command('file:clean-deleted')->dailyAt('03:30');

// 冗余计数校准：每天 04:00 校准题库题目数、分类题库数等
Schedule::command('data:recount')->dailyAt('04:00');

// 注销用户物理清除：每天 03:30 清除已注销超 30 天的用户数据（订单依法保留）
Schedule::command('user:purge-canceled')->dailyAt('03:30');
