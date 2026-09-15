<?php

declare(strict_types=1);

namespace App\Services\Console;

use App\Enums\ExamStatus;
use App\Exceptions\BusinessException;
use App\Models\ExamAnswer;
use App\Models\ExamRecord;
use App\Support\ErrorCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 考试记录服务（docs/04 §四 API-CSL-EXM-*）
 *
 * 规则：仅操作当前登录用户自己的考试记录。
 */
class ConsoleExamService
{
    /**
     * 考试记录列表
     */
    public function paginate(int $userId, array $filters, int $page, int $pageSize): LengthAwarePaginator
    {
        $query = ExamRecord::query()
            ->leftJoin('bank_question_banks', 'bank_question_banks.id', '=', 'exam_records.bank_id')
            ->where('exam_records.user_id', $userId)
            ->whereNull('exam_records.deleted_at')
            ->select('exam_records.*', 'bank_question_banks.title as bank_title');

        if (! empty($filters['bank_id'])) {
            $query->where('exam_records.bank_id', (int) $filters['bank_id']);
        }
        if (! empty($filters['keyword'])) {
            $keyword = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim((string) $filters['keyword']));
            $query->where('exam_records.paper_title', 'like', "%{$keyword}%");
        }

        return $query->orderByDesc('exam_records.id')
            ->paginate($pageSize, ['*'], 'page', $page)
            ->through(fn (ExamRecord $r) => $this->toItem($r));
    }

    /**
     * 考试记录详情（含作答明细）
     */
    public function detail(int $recordId, int $userId): array
    {
        $record = ExamRecord::whereKey($recordId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if ($record === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '考试记录不存在');
        }

        $answers = ExamAnswer::query()
            ->leftJoin('question_items', 'question_items.id', '=', 'exam_answers.question_id')
            ->where('exam_answers.record_id', $record->id)
            ->where('exam_answers.user_id', $userId)
            ->orderBy('exam_answers.id')
            ->get([
                'exam_answers.question_id',
                'exam_answers.user_answer',
                'exam_answers.is_correct',
                'exam_answers.score',
                'question_items.stem_preview',
            ])
            ->map(fn ($a) => [
                'question_id'   => (int) $a->question_id,
                'stem_preview'  => $a->stem_preview ?? '',
                'user_answer'   => $a->user_answer,
                'is_correct'    => (int) $a->is_correct,
                'score'         => number_format((float) $a->score, 2, '.', ''),
            ])
            ->all();

        return $this->toItem($record) + ['answers' => $answers];
    }

    /** ExamRecord 输出 */
    private function toItem(ExamRecord $r): array
    {
        return [
            'id'             => $r->id,
            'record_no'      => $r->record_no,
            'paper_title'    => $r->paper_title,
            'bank_id'        => (int) $r->bank_id,
            'bank_title'     => $r->bank_title ?? '',
            'total_count'    => (int) $r->total_count,
            'right_count'    => (int) $r->right_count,
            'wrong_count'    => (int) $r->wrong_count,
            'correct_rate'   => number_format((float) $r->correct_rate, 2, '.', ''),
            'get_score'      => number_format((float) $r->get_score, 2, '.', ''),
            'total_score'    => number_format((float) $r->total_score, 2, '.', ''),
            'duration_seconds' => (int) $r->duration_seconds,
            'is_passed'      => (int) $r->is_passed,
            'status'         => (int) $r->status,
            'status_text'    => ExamStatus::tryFrom((int) $r->status)?->label() ?? '',
            'submitted_at'   => $r->submitted_at?->toDateTimeString(),
            'created_at'     => $r->created_at?->toDateTimeString(),
        ];
    }
}
