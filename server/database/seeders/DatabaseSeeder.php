<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * 数据填充总入口
 *
 * 执行顺序：先系统域（权限 / 配置 / 管理员），再业务域（分类 / 套餐 / 运营位）
 * 命令：php artisan db:seed
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SystemInitSeeder::class,
            BusinessInitSeeder::class,
        ]);
    }
}
