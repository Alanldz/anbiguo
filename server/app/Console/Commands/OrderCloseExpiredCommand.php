<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 未支付订单自动关闭
 * 对应调度：server/routes/console.php → Schedule::command('order:close-expired')->everyTenMinutes()
 *
 * 阈值：调度注册未单独写分钟数；超时判定以 order_orders.expired_at（支付超时时间）为准，
 *       下单时写入 expired_at = now()+30min。关闭 status=待支付(0) 且 expired_at 已过的订单 → 已关闭(4)。
 * 说明：本命令属系统任务，无用户边界；状态一律用 OrderStatus 枚举。
 */
class OrderCloseExpiredCommand extends Command
{
    /** @var string 必须与调度注册完全一致 */
    protected $signature = 'order:close-expired';

    protected $description = '关闭超时未支付的订单（依赖 order_orders.expired_at）';

    public function handle(): int
    {
        $now = now();
        $count = 0;

        Order::where('status', OrderStatus::PENDING_PAYMENT->value)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', $now)
            ->chunkById(200, function ($orders) use (&$count, $now) {
                foreach ($orders as $order) {
                    $order->status = OrderStatus::CLOSED->value;
                    $order->updated_at = $now;
                    $order->save();
                    $count++;
                }
                $this->info("本批次已处理，累计关闭 {$count} 条");
            });

        $this->info("超时未支付订单关闭完成，共关闭 {$count} 条");
        Log::info('order:close-expired 完成', ['closed_count' => $count]);

        return self::SUCCESS;
    }
}
