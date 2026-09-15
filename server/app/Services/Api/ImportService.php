<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Enums\BankSourceType;
use App\Enums\BankStatus;
use App\Enums\ImportMode;
use App\Enums\ImportTaskStatus;
use App\Exceptions\BusinessException;
use App\Models\FileAsset;
use App\Models\QuestionBank;
use App\Models\QuestionImportTask;
use App\Models\UserMember;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\DB;

/**
 * 客户端导入服务（本期同步占位，真实 AI/文档/OCR 解析待迭代）
 * 台账：docs/04-API接口规范与登记表.md §三 API-IMP-001 ~ 005
 *
 * 占位规则：upload/manual/ocr 校验入参与会员配额后创建 question_import_tasks，
 * status=3 待校对，result_json={"pending":true}，progress=100。
 */
class ImportService
{
    /**
     * 上传文档导题（占位）
     */
    public function upload(int $userId, array $data): array
    {
        $this->assertQuota($userId);

        $file = FileAsset::whereKey((int) $data['file_id'])
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if ($file === null) {
            throw new BusinessException(ErrorCode::FILE_NOT_FOUND, '上传文件不存在或已被删除');
        }

        $bankId = (int) ($data['bank_id'] ?? 0);
        $bankTitle = trim((string) ($data['title'] ?? ''));
        $bankId = $this->resolveBank($userId, $bankId, $bankTitle);

        $task = $this->createTask($userId, ImportMode::DOC_IMPORT, $bankId, $file->id, $file->origin_name, $file->file_ext);

        return $this->toItem($task);
    }

    /**
     * 手动录入题目（占位：不直接落题，建手动录入导入任务）
     */
    public function manual(int $userId, array $data): array
    {
        $this->assertQuota($userId);

        $bankId = (int) ($data['bank_id'] ?? 0);
        $this->assertBankOwned($userId, $bankId);

        $task = $this->createTask($userId, ImportMode::MANUAL, $bankId, 0, '手动录入', '', [
            'pending'  => true,
            'manual'   => [
                'type'    => (int) ($data['type'] ?? 1),
                'title'   => (string) ($data['title'] ?? ''),
                'options' => $data['options'] ?? [],
                'answer'  => (string) ($data['answer'] ?? ''),
                'analysis' => (string) ($data['analysis'] ?? ''),
            ],
        ]);

        return $this->toItem($task);
    }

    /**
     * 拍照录题 OCR（占位：OCR 待接入，text 返回空串）
     */
    public function ocr(int $userId, array $data): array
    {
        $this->assertQuota($userId);

        $fileId = (int) ($data['file_id'] ?? 0);
        $file = null;
        if ($fileId > 0) {
            $file = FileAsset::whereKey($fileId)
                ->where('user_id', $userId)
                ->whereNull('deleted_at')
                ->first();
            if ($file === null) {
                throw new BusinessException(ErrorCode::FILE_NOT_FOUND, '上传图片不存在或已被删除');
            }
        }

        $bankId = (int) ($data['bank_id'] ?? 0);
        $bankId = $this->resolveBank($userId, $bankId, '');

        $task = $this->createTask(
            $userId,
            ImportMode::PHOTO_OCR,
            $bankId,
            $fileId,
            $file?->origin_name ?? '拍照录题',
            $file?->file_ext ?? '',
            ['pending' => true, 'text' => '']
        );

        $item = $this->toItem($task);
        $item['text'] = '';

        return $item;
    }

    /**
     * 查询导入任务进度
     */
    public function tasksShow(int $userId, int $id): array
    {
        $task = QuestionImportTask::with('bank:id,title')
            ->whereKey($id)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if ($task === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '导入任务不存在');
        }

        return [
            'id'            => $task->id,
            'bank_id'       => (int) $task->bank_id,
            'origin_name'   => $task->origin_name,
            'total_count'   => (int) $task->total_count,
            'parsed_count'  => (int) $task->success_count,
            'status'        => $this->statusText((int) $task->status),
            'fail_reason'   => (int) $task->status === ImportTaskStatus::FAILED->value ? $task->error_message : '',
            'status_text'   => ImportTaskStatus::tryFrom((int) $task->status)?->label() ?? '',
        ];
    }

    /**
     * 导入模板（固定结构）
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

    /** 创建占位导入任务 */
    private function createTask(int $userId, ImportMode $mode, int $bankId, int $fileId, string $originName, string $ext, array $result = []): QuestionImportTask
    {
        return DB::transaction(function () use ($userId, $mode, $bankId, $fileId, $originName, $ext, $result) {
            return QuestionImportTask::create([
                'task_no'      => $this->genTaskNo(),
                'user_id'      => $userId,
                'bank_id'      => $bankId,
                'file_id'      => $fileId,
                'origin_name'  => $originName,
                'file_ext'     => $ext,
                'import_mode'  => $mode->value,
                'total_count'  => 0,
                'success_count' => 0,
                'fail_count'   => 0,
                'progress'     => 100,
                'status'       => ImportTaskStatus::TO_PROOFREAD->value,
                'result_json'  => array_merge(['pending' => true], $result),
                'started_at'   => now(),
                'finished_at'  => now(),
            ]);
        });
    }

    /** 会员配额（简单实现：会员额度高于普通用户） */
    private function assertQuota(int $userId): void
    {
        $member = UserMember::where('user_id', $userId)->whereNull('deleted_at')->first();
        $isVip = $member !== null && (int) $member->level > 0;

        $dailyLimit = $isVip
            ? max((int) ($member->ai_import_quota ?? 0), 10)
            : (int) config('anbiguo.import.free_daily_limit', 3);

        $todayCount = QuestionImportTask::where('user_id', $userId)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($todayCount >= $dailyLimit) {
            throw new BusinessException(ErrorCode::QUOTA_EXHAUSTED);
        }
    }

    /** bank_id=0 且传 title 时先建题库（用户上传，待审核） */
    private function resolveBank(int $userId, int $bankId, string $bankTitle): int
    {
        if ($bankId > 0) {
            return $bankId;
        }

        if ($bankTitle === '') {
            return 0;
        }

        $bank = QuestionBank::create([
            'user_id'       => $userId,
            'category_id'   => 0,
            'title'         => $bankTitle,
            'source_type'   => BankSourceType::USER_UPLOAD->value,
            'charge_type'   => 1,
            'question_count' => 0,
            'chapter_count' => 0,
            'status'        => BankStatus::PENDING->value,
            'sort_order'    => 0,
        ]);

        return $bank->id;
    }

    private function assertBankOwned(int $userId, int $bankId): void
    {
        if ($bankId <= 0) {
            throw new BusinessException(ErrorCode::BANK_NOT_FOUND, '请选择目标题库');
        }

        $owned = QuestionBank::whereKey($bankId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->exists();

        if (! $owned) {
            throw new BusinessException(ErrorCode::BANK_NO_PERMISSION, '无权向该题库录入题目');
        }
    }

    /** 对齐前端 ImportTask.status 取值（pending/parsing/proofread/success/failed） */
    private function statusText(int $status): string
    {
        return match ($status) {
            ImportTaskStatus::PENDING->value      => 'pending',
            ImportTaskStatus::PARSING->value      => 'parsing',
            ImportTaskStatus::TO_PROOFREAD->value => 'proofread',
            ImportTaskStatus::COMPLETED->value    => 'success',
            ImportTaskStatus::FAILED->value       => 'failed',
            default                                => 'pending',
        };
    }

    private function toItem(QuestionImportTask $task): array
    {
        return [
            'id'          => $task->id,
            'task_id'     => $task->id,
            'task_no'     => $task->task_no,
            'bank_id'     => (int) $task->bank_id,
            'status'      => $this->statusText((int) $task->status),
            'status_text' => ImportTaskStatus::tryFrom((int) $task->status)?->label() ?? '',
        ];
    }

    private function genTaskNo(): string
    {
        return 'IMP'.date('Ymd').str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }
}
