<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\BankChapter;
use App\Models\QuestionBank;
use App\Models\QuestionItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 冗余计数校准
 * 对应调度：server/routes/console.php → Schedule::command('data:recount')->dailyAt('04:00')
 *
 * 逻辑：
 *   - bank_question_banks.question_count = 该题库下未软删题目数（whereNull deleted_at）
 *   - bank_chapters.question_count       = 该章节下未软删题目数
 *   - user_daily_stats 无需重算（见文档约定）
 * 说明：本命令属系统任务，无用户边界；题目计数口径为「未软删题目」，与题库实际题目数对齐。
 */
class DataRecountCommand extends Command
{
    /** @var string 必须与调度注册完全一致 */
    protected $signature = 'data:recount';

    protected $description = '重算题库 / 章节的题目冗余计数，与题目实际数量对齐';

    public function handle(): int
    {
        $bankFixed = 0;
        $chapterFixed = 0;

        // 题库题目数校准
        QuestionBank::chunkById(200, function ($banks) use (&$bankFixed) {
            foreach ($banks as $bank) {
                $actual = QuestionItem::where('bank_id', $bank->id)
                    ->whereNull('deleted_at')
                    ->count();
                if ((int) $bank->question_count !== (int) $actual) {
                    $bank->question_count = $actual;
                    $bank->save();
                    $bankFixed++;
                }
            }
        });
        $this->info("题库题目数校准完成，修正 {$bankFixed} 个");

        // 章节题目数校准
        BankChapter::chunkById(200, function ($chapters) use (&$chapterFixed) {
            foreach ($chapters as $chapter) {
                $actual = QuestionItem::where('chapter_id', $chapter->id)
                    ->whereNull('deleted_at')
                    ->count();
                if ((int) $chapter->question_count !== (int) $actual) {
                    $chapter->question_count = $actual;
                    $chapter->save();
                    $chapterFixed++;
                }
            }
        });
        $this->info("章节题目数校准完成，修正 {$chapterFixed} 个");

        Log::info('data:recount 完成', [
            'bank_fixed'    => $bankFixed,
            'chapter_fixed' => $chapterFixed,
        ]);

        return self::SUCCESS;
    }
}
