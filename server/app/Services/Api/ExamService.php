<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Enums\BankStatus;
use App\Enums\ExamStatus;
use App\Enums\PaperType;
use App\Enums\QuestionType;
use App\Enums\WrongQuestionStatus;
use App\Exceptions\BusinessException;
use App\Models\ExamAnswer;
use App\Models\ExamPaper;
use App\Models\ExamPaperQuestion;
use App\Models\ExamRecord;
use App\Models\QuestionBank;
use App\Models\QuestionItem;
use App\Models\QuestionOption;
use App\Models\UserDailyStat;
use App\Models\UserWrongQuestion;
use App\Support\ErrorCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 客户端考试服务（组卷 / 试卷详情 / 交卷幂等 / 成绩回顾 / 记录列表）
 * 台账：docs/04-API接口规范与登记表.md §三 API-EXM-001 ~ 005
 */
class ExamService
{
    /**
     * 发起 / 生成试卷（从题库抽题）
     */
    public function createPaper(int $userId, array $data): array
    {
        $bankId = (int) $data['bank_id'];
        $bank = $this->assertBankVisible($bankId, $userId);

        $want = max(1, (int) ($data['question_count'] ?? 10));

        $query = QuestionItem::where('bank_id', $bankId)
            ->where('status', 1)
            ->whereNull('deleted_at');

        if (! empty($data['types']) && is_array($data['types'])) {
            $query->whereIn('question_type', array_map('intval', $data['types']));
        }

        $questions = $query->inRandomOrder()->limit($want)->get();

        if ($questions->isEmpty()) {
            throw new BusinessException(ErrorCode::EXAM_QUESTION_EMPTY);
        }

        $paper = DB::transaction(function () use ($userId, $bank, $questions, $data, $want) {
            $totalScore = 0.0;
            foreach ($questions as $q) {
                $totalScore += (float) $q->score;
            }
            $totalScore = round($totalScore, 2);
            $passScore = round($totalScore * 0.6, 2);

            $paper = ExamPaper::create([
                'paper_no'        => $this->genPaperNo(),
                'user_id'         => $userId,
                'bank_id'         => $bank->id,
                'title'           => $bank->title.' 模拟考试',
                'paper_type'      => PaperType::MOCK_EXAM->value,
                'generate_mode'   => 1, // 1=随机抽题（generate_mode 暂无枚举，见迁移注释）
                'question_count'  => $questions->count(),
                'total_score'     => $totalScore,
                'pass_score'      => $passScore,
                'duration_minutes' => max(0, (int) ($data['duration_minutes'] ?? 0)),
                'config_json'     => '',
                'status'          => 1,
            ]);

            $sort = 1;
            foreach ($questions as $q) {
                ExamPaperQuestion::create([
                    'paper_id'   => $paper->id,
                    'question_id' => $q->id,
                    'bank_id'    => $q->bank_id,
                    'sort_order' => $sort,
                    'score'      => $q->score,
                ]);
                $sort++;
            }

            return $paper;
        });

        return $this->paperToArray($paper, $userId);
    }

    /**
     * 试卷详情（含题目）
     */
    public function paperDetail(int $userId, int $paperId): array
    {
        $paper = ExamPaper::whereKey($paperId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if ($paper === null) {
            throw new BusinessException(ErrorCode::PAPER_NOT_FOUND);
        }

        return $this->paperToArray($paper, $userId);
    }

    /**
     * 交卷（幂等：同用户同试卷已交卷则直接返回已有成绩）
     */
    public function submit(int $userId, array $data): array
    {
        $paperId = (int) $data['paper_id'];

        $paper = ExamPaper::whereKey($paperId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if ($paper === null) {
            throw new BusinessException(ErrorCode::PAPER_NOT_FOUND);
        }

        // 幂等：已交卷 / 超时自动交卷的记录直接返回
        $existing = ExamRecord::where('user_id', $userId)
            ->where('paper_id', $paperId)
            ->whereIn('status', [ExamStatus::SUBMITTED->value, ExamStatus::TIMEOUT->value])
            ->first();

        if ($existing !== null) {
            return $this->recordToArray($existing);
        }

        $answersInput = [];
        foreach ((array) ($data['answers'] ?? []) as $a) {
            if (isset($a['question_id'])) {
                $answersInput[(int) $a['question_id']] = (string) ($a['answer'] ?? '');
            }
        }

        $paperQuestions = ExamPaperQuestion::with('question')
            ->where('paper_id', $paperId)
            ->orderBy('sort_order')
            ->get();

        if ($paperQuestions->isEmpty()) {
            throw new BusinessException(ErrorCode::EXAM_QUESTION_EMPTY);
        }

        $costSeconds = max(0, (int) ($data['cost_seconds'] ?? 0));

        $record = DB::transaction(function () use ($userId, $paper, $paperQuestions, $answersInput, $costSeconds) {
            $totalCount = $paperQuestions->count();
            $answeredCount = 0;
            $rightCount = 0;
            $wrongCount = 0;
            $getScore = 0.0;

            $answerRows = [];
            $wrongUpdates = [];

            foreach ($paperQuestions as $pq) {
                $question = $pq->question;
                if ($question === null) {
                    continue;
                }
                $userAnswer = $answersInput[(int) $question->id] ?? '';
                $type = QuestionType::tryFrom((int) $question->question_type) ?? QuestionType::SINGLE_CHOICE;
                $isCorrect = $userAnswer !== '' && $this->normalize($type, (string) $question->answer) === $this->normalize($type, $userAnswer);

                $answeredCount++;
                $score = $isCorrect ? (float) $pq->score : 0.0;
                $getScore += $score;

                if ($isCorrect) {
                    $rightCount++;
                } else {
                    $wrongCount++;
                    $wrongUpdates[(int) $question->id] = $userAnswer;
                }

                $answerRows[] = [
                    'question_id' => $question->id,
                    'user_answer' => $userAnswer,
                    'is_correct'  => $isCorrect ? 1 : 0,
                    'score'       => round($score, 2),
                ];
            }

            $totalScore = (float) $paper->total_score;
            $correctRate = $totalCount > 0 ? round($rightCount / $totalCount * 100, 2) : 0;
            $getScore = round($getScore, 2);
            $isPassed = $totalScore > 0 ? ($getScore >= (float) $paper->pass_score ? 1 : 0) : 0;

            $record = ExamRecord::create([
                'record_no'      => $this->genRecordNo(),
                'user_id'        => $userId,
                'paper_id'       => $paper->id,
                'bank_id'        => $paper->bank_id,
                'paper_title'    => $paper->title,
                'total_count'    => $totalCount,
                'answered_count' => $answeredCount,
                'right_count'    => $rightCount,
                'wrong_count'    => $wrongCount,
                'unanswer_count' => $totalCount - $answeredCount,
                'total_score'    => $totalScore,
                'get_score'      => $getScore,
                'correct_rate'   => $correctRate,
                'duration_seconds' => $costSeconds,
                'is_passed'      => $isPassed,
                'status'         => ExamStatus::SUBMITTED->value,
                'started_at'     => now(),
                'submitted_at'   => now(),
            ]);

            foreach ($answerRows as $row) {
                ExamAnswer::create(array_merge($row, [
                    'record_id' => $record->id,
                    'paper_id'  => $paper->id,
                    'user_id'   => $userId,
                ]));
            }

            // 错题本（来源=考试）
            foreach ($wrongUpdates as $questionId => $userAnswer) {
                $this->upsertWrong($userId, $questionId, $userAnswer);
            }

            // 当日统计（考试计次）
            $this->touchDailyStat($userId, $rightCount, $wrongCount, $costSeconds, $answeredCount);

            return $record;
        });

        return $this->recordToArray($record);
    }

    /**
     * 成绩与试卷回顾（含作答明细）
     */
    public function recordDetail(int $userId, int $recordId): array
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

        return $this->recordToArray($record) + ['answers' => $answers];
    }

    /**
     * 考试记录列表（分页）
     */
    public function paginateRecords(int $userId, array $filters, int $page, int $pageSize): LengthAwarePaginator
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
            ->through(fn (ExamRecord $r) => $this->recordToArray($r));
    }

    /** 试卷 → ExamPaper 数组（含题目） */
    private function paperToArray(ExamPaper $paper, int $userId): array
    {
        $questions = ExamPaperQuestion::with('question')
            ->where('paper_id', $paper->id)
            ->orderBy('sort_order')
            ->get()
            ->map(function (ExamPaperQuestion $pq) {
                $q = $pq->question;

                if ($q === null) {
                    return null;
                }

                $options = QuestionOption::where('question_id', $q->id)
                    ->whereNull('deleted_at')
                    ->orderBy('sort_order')
                    ->orderBy('option_key')
                    ->get(['option_key', 'content'])
                    ->map(fn ($o) => ['key' => $o->option_key, 'content' => $o->content])
                    ->all();

                return [
                    'id'            => $q->id,
                    'bank_id'       => (int) $q->bank_id,
                    'type'          => (int) $q->question_type,
                    'title'         => (string) $q->stem,
                    'options'       => $options,
                    'answer'        => (string) $q->answer,
                    'analysis'      => (string) $q->analysis,
                    'is_favorited'  => false,
                    'note'          => '',
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            'id'               => $paper->id,
            'title'            => $paper->title,
            'bank_id'          => (int) $paper->bank_id,
            'duration_minutes' => (int) $paper->duration_minutes,
            'total_score'      => number_format((float) $paper->total_score, 2, '.', ''),
            'question_count'   => (int) $paper->question_count,
            'questions'        => $questions,
        ];
    }

    /** ExamRecord → 前端 ExamRecord 形状 */
    private function recordToArray(ExamRecord $r): array
    {
        return [
            'id'              => $r->id,
            'paper_id'        => (int) $r->paper_id,
            'title'           => $r->paper_title,
            'score'           => number_format((float) $r->get_score, 2, '.', ''),
            'total_score'     => number_format((float) $r->total_score, 2, '.', ''),
            'correct_count'   => (int) $r->right_count,
            'question_count'  => (int) $r->total_count,
            'cost_seconds'    => (int) $r->duration_seconds,
            'created_at'      => $r->created_at?->toDateTimeString(),
            'is_passed'       => (int) $r->is_passed,
            'wrong_count'     => (int) $r->wrong_count,
            'correct_rate'    => number_format((float) $r->correct_rate, 2, '.', ''),
            'status'          => (int) $r->status,
            'status_text'     => ExamStatus::tryFrom((int) $r->status)?->label() ?? '',
        ];
    }

    private function assertBankVisible(int $bankId, int $userId): QuestionBank
    {
        $bank = QuestionBank::whereKey($bankId)->whereNull('deleted_at')->first();

        if ($bank === null) {
            throw new BusinessException(ErrorCode::BANK_NOT_FOUND);
        }

        $visible = (int) $bank->user_id === $userId
            || (int) $bank->user_id === 0
            || (int) $bank->status === BankStatus::NORMAL->value;

        if (! $visible) {
            throw new BusinessException(ErrorCode::BANK_NOT_FOUND);
        }

        return $bank;
    }

    private function upsertWrong(int $userId, int $questionId, string $userAnswer): void
    {
        $question = QuestionItem::whereKey($questionId)->whereNull('deleted_at')->first();
        if ($question === null) {
            return;
        }

        $wq = UserWrongQuestion::where('user_id', $userId)
            ->where('question_id', $questionId)
            ->whereNull('deleted_at')
            ->first();

        if ($wq === null) {
            UserWrongQuestion::create([
                'user_id'      => $userId,
                'question_id'  => $questionId,
                'bank_id'      => $question->bank_id,
                'wrong_count'  => 1,
                'last_wrong_at' => now(),
                'source_type'  => 2, // 1=练习 2=考试（暂无枚举，与 Console 端约定一致）
                'last_answer'  => $userAnswer,
                'status'       => WrongQuestionStatus::IN_BOOK->value,
            ]);

            return;
        }

        $wq->wrong_count = (int) $wq->wrong_count + 1;
        $wq->last_wrong_at = now();
        $wq->last_answer = $userAnswer;
        $wq->status = WrongQuestionStatus::IN_BOOK->value;
        $wq->save();
    }

    private function touchDailyStat(int $userId, int $right, int $wrong, int $duration, int $answered): void
    {
        $today = now()->toDateString();
        $stat = UserDailyStat::where('user_id', $userId)->where('stat_date', $today)->first();

        if ($stat === null) {
            UserDailyStat::create([
                'user_id'        => $userId,
                'stat_date'      => $today,
                'answer_count'   => $answered,
                'right_count'    => $right,
                'wrong_count'    => $wrong,
                'practice_count' => 0,
                'exam_count'     => 1,
                'duration_seconds' => $duration,
            ]);

            return;
        }

        $stat->answer_count += $answered;
        $stat->right_count += $right;
        $stat->wrong_count += $wrong;
        $stat->exam_count += 1;
        $stat->duration_seconds += $duration;
        $stat->save();
    }

    private function normalize(QuestionType $type, string $answer): string
    {
        $answer = trim($answer);

        if ($type === QuestionType::JUDGE) {
            $map = ['对' => 'A', '错' => 'B', '正确' => 'A', '错误' => 'B'];

            return $map[mb_strtolower($answer)] ?? mb_strtoupper($answer);
        }

        if ($type === QuestionType::MULTIPLE_CHOICE) {
            $letters = preg_split('//u', mb_strtoupper(preg_replace('/[^A-Ha-h]/u', '', $answer)), -1, PREG_SPLIT_NO_EMPTY);
            sort($letters);

            return implode('', $letters);
        }

        return mb_strtoupper(preg_replace('/\s+/', '', $answer));
    }

    private function genPaperNo(): string
    {
        return 'EP'.date('Ymd').str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function genRecordNo(): string
    {
        return 'ER'.date('Ymd').str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }
}
