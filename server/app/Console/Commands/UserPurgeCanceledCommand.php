<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 注销用户 30 天物理清除
 * 对应调度：server/routes/console.php → Schedule::command('user:purge-canceled')->dailyAt('03:30')
 * 台账：docs/04-API接口规范与登记表.md §三 API-USER-004（注销后 30 天物理清除）
 *
 * 范围：user_accounts 中 status=已注销（UserStatus::CANCELED=4）且 updated_at（状态最后变更时间）
 *       早于 30 天前的账号，物理删除该用户以 user_id 关联的数据行。
 *
 * ⚠️ 交易记录依法留存：order_orders / order_payments 一律保留，不做物理删除。
 * ⚠️ 本期保留（涉及深层级联或外部资源，待后续迭代扩展清理策略）：
 *   - bank_question_banks（用户创建的题库，级联题目/章节，软删表且官方市场可能引用题目冗余）
 *   - file_assets / file_categories（物理删除会产生 OSS 孤儿对象，OSS 清理见 file:clean-deleted 后续接入）
 *   - question_import_tasks（关联任务产物与配额流水）
 *   - exam_papers / exam_records / exam_answers（考试流水，含成绩追溯）
 */
class UserPurgeCanceledCommand extends Command
{
    /** @var string 必须与调度注册完全一致 */
    protected $signature = 'user:purge-canceled';

    protected $description = '物理删除已注销超过 30 天的用户账号及其关联数据（订单依法保留）';

    /**
     * 以 user_id 关联、需要物理清除的表清单（逐表 chunkById 删除）
     *
     * @var string[]
     */
    private const PURGE_TABLES = [
        'user_favorite_questions',
        'user_question_notes',
        'user_wrong_questions',
        'user_practice_records',
        'user_notifications',
        'user_members',
        'user_daily_stats',
        'user_profiles',
        'question_reports',
        'sys_feedbacks',
    ];

    /** 每批删除条数 */
    private const CHUNK_SIZE = 500;

    public function handle(): int
    {
        $threshold = now()->subDays(30);
        $purgedUsers = 0;
        $purgedRows = 0;

        // 逐个账号处理：updated_at 为状态最后变更时间（注销操作时间）
        DB::table('user_accounts')
            ->where('status', UserStatus::CANCELED->value)
            ->where('updated_at', '<', $threshold)
            ->select('id')
            ->chunkById(self::CHUNK_SIZE, function ($users) use ($threshold, &$purgedUsers, &$purgedRows) {
                foreach ($users as $user) {
                    $purgedRows += $this->purgeUserData((int) $user->id);
                    $purgedUsers++;
                }
                $this->info("本批次已处理，累计清除账号 {$purgedUsers} 个、数据行 {$purgedRows} 条（阈值 {$threshold}）");
            }, 'id');

        $this->info("注销用户物理清除完成，共清除账号 {$purgedUsers} 个、数据行 {$purgedRows} 条（订单依法保留）");
        Log::info('user:purge-canceled 完成', [
            'purged_users' => $purgedUsers,
            'purged_rows' => $purgedRows,
        ]);

        return self::SUCCESS;
    }

    /**
     * 物理清除单个用户以 user_id 关联的数据行，最后删除账号行
     */
    private function purgeUserData(int $userId): int
    {
        $deleted = 0;

        foreach (self::PURGE_TABLES as $table) {
            $deleted += $this->deleteByUserIdChunked($table, $userId);
        }

        // 账号行最后删除
        $deleted += DB::table('user_accounts')->where('id', $userId)->delete();

        return $deleted;
    }

    /**
     * 按主键分批物理删除指定用户的表数据（避免大事务与长锁）
     */
    private function deleteByUserIdChunked(string $table, int $userId): int
    {
        $deleted = 0;

        DB::table($table)
            ->where('user_id', $userId)
            ->select('id')
            ->chunkById(self::CHUNK_SIZE, function ($rows) use ($table, &$deleted) {
                $ids = array_map(fn ($row) => (int) $row->id, $rows->all());
                if ($ids !== []) {
                    $deleted += DB::table($table)->whereIn('id', $ids)->delete();
                }
            }, 'id');

        return $deleted;
    }
}
