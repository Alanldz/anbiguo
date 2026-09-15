<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\MemberStatus;
use App\Models\UserMember;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 会员过期扫描
 * 对应调度：server/routes/console.php → Schedule::command('member:expire-scan')->dailyAt('00:30')
 *
 * 逻辑：扫描 user_members 中 status=生效(1) 且存在过期时间且已过期（expired_at < now()）的记录，
 *       置为 已过期(2)。永久会员 expired_at 为 null，自然跳过。
 * 说明：本命令属系统任务，无用户边界；状态一律用 MemberStatus 枚举。
 */
class MemberExpireScanCommand extends Command
{
    /** @var string 必须与调度注册完全一致 */
    protected $signature = 'member:expire-scan';

    protected $description = '扫描并将已过期的用户会员置为「已过期」';

    public function handle(): int
    {
        $now = now();
        $count = 0;

        UserMember::where('status', MemberStatus::ACTIVE->value)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', $now)
            ->chunkById(200, function ($members) use (&$count, $now) {
                foreach ($members as $member) {
                    $member->status = MemberStatus::EXPIRED->value;
                    $member->save();
                    $count++;
                }
                $this->info("本批次已处理，累计标记 {$count} 条");
            });

        $this->info("会员过期扫描完成，共标记 {$count} 条为已过期");
        Log::info('member:expire-scan 完成', ['expired_count' => $count]);

        return self::SUCCESS;
    }
}
