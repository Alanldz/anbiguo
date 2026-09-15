<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\FileBizType;
use App\Models\FileAsset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 临时文件清理（软删）
 * 对应调度：server/routes/console.php → Schedule::command('file:clean-temp')->dailyAt('03:00')
 *
 * 阈值：调度注册写明「清理 7 天前到期的 temp 文件」→ expired_at < now()-7天。
 * 逻辑：软删 file_assets 中 biz_type=临时文件(9) 且已过期 7 天以上、且未软删的记录（SoftDeletes 置 deleted_at）。
 * 说明：本命令属系统任务，无用户边界；业务类型一律用 FileBizType 枚举。
 */
class FileCleanTempCommand extends Command
{
    /** @var string 必须与调度注册完全一致 */
    protected $signature = 'file:clean-temp';

    protected $description = '清理过期的临时文件记录（软删 biz_type=temp 且过期 7 天以上）';

    public function handle(): int
    {
        $threshold = now()->subDays(7);
        $count = 0;

        FileAsset::where('biz_type', FileBizType::TEMP->value)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', $threshold)
            ->whereNull('deleted_at')
            ->chunkById(200, function ($files) use (&$count) {
                foreach ($files as $file) {
                    $file->delete(); // 软删（SoftDeletes）
                    $count++;
                }
                $this->info("本批次已处理，累计软删 {$count} 条");
            });

        $this->info("临时文件清理完成，共软删 {$count} 条");
        Log::info('file:clean-temp 完成', ['soft_deleted_count' => $count]);

        return self::SUCCESS;
    }
}
