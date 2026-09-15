<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\FileAsset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 已软删文件物理清理（仅删 DB 记录，不动 OSS 对象）
 * 对应调度：server/routes/console.php → Schedule::command('file:clean-deleted')->dailyAt('03:30')
 *
 * 阈值：调度注册写明「清理已软删 30 天的文件」→ deleted_at < now()-30天。
 * 条件：仅清理 ref_count=0（无引用）的已软删记录；OSS 对象的物理删除不在本期范围（保留期与 OSS 清理待后续接入）。
 * 说明：本命令属系统任务，无用户边界；保留期以调度注册为准（30 天）。
 */
class FileCleanDeletedCommand extends Command
{
    /** @var string 必须与调度注册完全一致 */
    protected $signature = 'file:clean-deleted';

    protected $description = '物理清理已软删超过 30 天且无引用的文件记录（仅 DB，不动 OSS）';

    public function handle(): int
    {
        $threshold = now()->subDays(30);
        $count = 0;

        FileAsset::onlyTrashed()
            ->where('deleted_at', '<', $threshold)
            ->where('ref_count', 0)
            ->chunkById(200, function ($files) use (&$count) {
                foreach ($files as $file) {
                    $file->forceDelete(); // 物理删 DB 记录（OSS 对象不在本期处理）
                    $count++;
                }
                $this->info("本批次已处理，累计删除 {$count} 条");
            });

        $this->info("已软删文件物理清理完成，共删除 DB 记录 {$count} 条");
        Log::info('file:clean-deleted 完成', ['purged_count' => $count]);

        return self::SUCCESS;
    }
}
