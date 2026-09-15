<?php

declare(strict_types=1);

namespace App\Services\Console;

use App\Enums\BankSourceType;
use App\Enums\BankStatus;
use App\Enums\ImportMode;
use App\Enums\ImportTaskStatus;
use App\Exceptions\BusinessException;
use App\Models\FileAsset;
use App\Models\QuestionBank;
use App\Models\QuestionImportTask;
use App\Support\ErrorCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 导入任务服务（docs/04 §四 API-CSL-IMP-*）
 *
 * 本期为同步占位实现：校验文件归属后创建任务记录，status=3 待校对，
 * result_json 写入占位结构，progress=100、total_count=0。真实 AI/文档解析后续迭代接入。
 */
class ConsoleImportService
{
    /**
     * 创建导入任务（同步占位）
     */
    public function create(int $userId, array $data): array
    {
        $file = FileAsset::whereKey((int) $data['file_id'])
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if ($file === null) {
            throw new BusinessException(ErrorCode::FILE_NOT_FOUND, '上传文件不存在或已被删除');
        }

        $bankId = (int) ($data['bank_id'] ?? 0);
        $bankTitle = trim((string) ($data['bank_title'] ?? ''));

        return DB::transaction(function () use ($userId, $data, $file, $bankId, $bankTitle) {
            // bank_id=0 且传 bank_title 时先建题库
            if ($bankId <= 0 && $bankTitle !== '') {
                $bank = QuestionBank::create([
                    'user_id'      => $userId,
                    'category_id'  => 0,
                    'title'        => $bankTitle,
                    'source_type'  => BankSourceType::USER_UPLOAD->value,
                    'charge_type'  => 1,
                    'question_count' => 0,
                    'chapter_count'  => 0,
                    'status'         => BankStatus::PENDING->value,
                    'sort_order'     => 0,
                ]);
                $bankId = $bank->id;
            }

            $importMode = isset($data['import_mode']) && ImportMode::tryFrom((int) $data['import_mode']) !== null
                ? (int) $data['import_mode']
                : ImportMode::DOC_IMPORT->value;

            $task = QuestionImportTask::create([
                'task_no'      => $this->genTaskNo(),
                'user_id'      => $userId,
                'bank_id'      => $bankId,
                'file_id'      => $file->id,
                'origin_name'  => $file->origin_name,
                'file_ext'     => $file->file_ext,
                'import_mode'  => $importMode,
                'total_count'  => 0,
                'success_count' => 0,
                'fail_count'   => 0,
                'progress'     => 100,
                'status'       => ImportTaskStatus::TO_PROOFREAD->value,
                'result_json'  => ['pending' => true, 'note' => 'AI 解析待后续迭代'],
                'started_at'   => now(),
                'finished_at'  => now(),
            ]);

            return [
                'id'         => $task->id,
                'task_no'    => $task->task_no,
                'status'     => $task->status,
                'status_text' => ImportTaskStatus::TO_PROOFREAD->label(),
                'bank_id'    => $task->bank_id,
            ];
        });
    }

    /**
     * 导入任务列表
     */
    public function paginate(int $userId, array $filters, int $page, int $pageSize): LengthAwarePaginator
    {
        $query = QuestionImportTask::with('bank:id,title')
            ->where('user_id', $userId)
            ->whereNull('deleted_at');

        if (! empty($filters['status'])) {
            $query->where('status', (int) $filters['status']);
        }

        return $query->orderByDesc('id')
            ->paginate($pageSize, ['*'], 'page', $page)
            ->through(fn (QuestionImportTask $t) => $this->toItem($t));
    }

    /**
     * 导入任务详情（含解析结果）
     */
    public function detail(int $taskId, int $userId): array
    {
        $task = QuestionImportTask::with('bank:id,title')
            ->whereKey($taskId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if ($task === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '导入任务不存在');
        }

        return $this->toItem($task) + [
            'result' => is_array($task->result_json) ? $task->result_json : [],
        ];
    }

    /**
     * 删除导入任务
     */
    public function delete(int $taskId, int $userId): void
    {
        $task = QuestionImportTask::whereKey($taskId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if ($task === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '导入任务不存在');
        }

        $task->delete();
    }

    /**
     * 导入模板说明
     */
    public function template(): array
    {
        return [
            'columns' => [
                ['name' => '题干', 'required' => true, 'desc' => '题目内容，支持富文本'],
                ['name' => '题型', 'required' => true, 'desc' => '单选/多选/判断/填空/简答'],
                ['name' => '选项', 'required' => false, 'desc' => '选择题填写，格式：A.选项内容|B.选项内容'],
                ['name' => '答案', 'required' => true, 'desc' => '选择题填选项字母，判断题填 对/错'],
                ['name' => '解析', 'required' => false, 'desc' => '答案解析'],
                ['name' => '难度', 'required' => false, 'desc' => '易/中/难'],
                ['name' => '章节', 'required' => false, 'desc' => '所属章节名称'],
            ],
            'sample_url' => '',
        ];
    }

    /** ImportTaskItem 输出 */
    private function toItem(QuestionImportTask $t): array
    {
        return [
            'id'            => $t->id,
            'task_no'       => $t->task_no,
            'bank_id'       => (int) $t->bank_id,
            'bank_title'    => $t->bank?->title ?? '',
            'origin_name'   => $t->origin_name,
            'file_ext'      => $t->file_ext,
            'import_mode'   => (int) $t->import_mode,
            'import_mode_text' => ImportMode::tryFrom((int) $t->import_mode)?->label() ?? '',
            'total_count'   => (int) $t->total_count,
            'success_count' => (int) $t->success_count,
            'fail_count'    => (int) $t->fail_count,
            'progress'      => (int) $t->progress,
            'status'        => (int) $t->status,
            'status_text'   => ImportTaskStatus::tryFrom((int) $t->status)?->label() ?? '',
            'error_message' => $t->error_message,
            'started_at'    => $t->started_at?->toDateTimeString(),
            'finished_at'   => $t->finished_at?->toDateTimeString(),
            'created_at'    => $t->created_at?->toDateTimeString(),
        ];
    }

    /** 生成任务编号 IMP+yyyyMMdd+6 位随机 */
    private function genTaskNo(): string
    {
        return 'IMP'.date('Ymd').str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }
}
