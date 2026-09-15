<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Enums\BankStatus;
use App\Enums\PracticeMode;
use App\Enums\PracticeStatus;
use App\Enums\QuestionReportType;
use App\Enums\QuestionType;
use App\Enums\WrongQuestionStatus;
use App\Exceptions\BusinessException;
use App\Models\QuestionBank;
use App\Models\QuestionItem;
use App\Models\QuestionOption;
use App\Models\QuestionReport;
use App\Models\UserDailyStat;
use App\Models\UserFavoriteQuestion;
use App\Models\UserPracticeRecord;
use App\Models\UserQuestionNote;
use App\Models\UserWrongQuestion;
use App\Support\ErrorCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 题目与练习服务
 * 台账：docs/04-API接口规范与登记表.md §三 API-QUE-001 ~ 005
 */
class QuestionPracticeService
{
    /**
     * 练习取题列表（支持章节 / 题型筛选与分页；mode=sequence|random|chapter）
     */
    public function listQuestions(int $userId, int $bankId, array $filters, int $page, int $pageSize): LengthAwarePaginator
    {
        $this->assertBankVisible($bankId, $userId);

        $query = QuestionItem::query()
            ->where('bank_id', $bankId)
            ->where('status', 1)
            ->whereNull('deleted_at');

        if (! empty($filters['chapter_id'])) {
            $query->where('chapter_id', (int) $filters['chapter_id']);
        }
        if (! empty($filters['question_type'])) {
            $query->where('question_type', (int) $filters['question_type']);
        }

        $mode = (string) ($filters['mode'] ?? 'sequence');
        if ($mode === 'random') {
            $query->inRandomOrder();
        } elseif ($mode === 'chapter') {
            $query->orderBy('chapter_id')->orderBy('sort_order');
        } else {
            $query->orderBy('sort_order')->orderBy('id');
        }

        return $query->paginate($pageSize, ['*'], 'page', $page)
            ->through(function (QuestionItem $q) use ($userId) {
                return $this->toQuestionArray($q, $userId);
            });
    }

    /**
     * 提交单题作答（判分 + 维护练习/错题/统计冗余）
     */
    public function answer(int $userId, int $questionId, string $userAnswer, ?int $costSeconds): array
    {
        $question = QuestionItem::whereKey($questionId)->whereNull('deleted_at')->first();

        if ($question === null) {
            throw new BusinessException(ErrorCode::QUESTION_NOT_FOUND);
        }

        $type = QuestionType::tryFrom((int) $question->question_type) ?? QuestionType::SINGLE_CHOICE;
        $correct = $this->normalize($type, (string) $question->answer) === $this->normalize($type, $userAnswer);

        DB::transaction(function () use ($userId, $question, $userAnswer, $correct, $costSeconds) {
            // ① 单题练习记录
            UserPracticeRecord::create([
                'user_id'        => $userId,
                'bank_id'        => $question->bank_id,
                'chapter_id'     => $question->chapter_id,
                'practice_mode'  => PracticeMode::SEQUENCE->value,
                'total_count'    => 1,
                'answered_count' => 1,
                'right_count'    => $correct ? 1 : 0,
                'wrong_count'    => $correct ? 0 : 1,
                'correct_rate'   => $correct ? 100 : 0,
                'duration_seconds' => max(0, (int) $costSeconds),
                'status'         => PracticeStatus::COMPLETED->value,
                'started_at'     => now(),
                'finished_at'    => now(),
            ]);

            // ② 当日统计冗余
            $this->touchDailyStat($userId, $correct ? 1 : 0, $correct ? 0 : 1, max(0, (int) $costSeconds));

            // ③ 答错维护错题本（同题重复错累计 wrong_count，不重复建行）
            if (! $correct) {
                $this->upsertWrong($userId, $question, $userAnswer);
            }

            // ④ 题目冗余计数
            $question->answer_count = (int) $question->answer_count + 1;
            if ($correct) {
                $question->right_count = (int) $question->right_count + 1;
            }
            $total = (int) $question->answer_count;
            $question->correct_rate = $total > 0 ? round((int) $question->right_count / $total * 100, 2) : 0;
            $question->save();
        });

        return [
            'correct'  => $correct,
            'answer'   => (string) $question->answer,
            'analysis' => (string) $question->analysis,
        ];
    }

    /**
     * 收藏 / 取消收藏
     */
    public function favorite(int $userId, int $questionId, bool $favorite): void
    {
        $question = $this->assertQuestion($questionId);

        if ($favorite) {
            UserFavoriteQuestion::firstOrCreate(
                ['user_id' => $userId, 'question_id' => $questionId],
                ['bank_id' => $question->bank_id, 'folder_name' => '默认收藏夹']
            );
        } else {
            UserFavoriteQuestion::where('user_id', $userId)
                ->where('question_id', $questionId)
                ->whereNull('deleted_at')
                ->delete();
        }
    }

    /**
     * 写 / 改笔记（upsert）
     */
    public function saveNote(int $userId, int $questionId, string $content): void
    {
        $question = $this->assertQuestion($questionId);

        UserQuestionNote::updateOrCreate(
            ['user_id' => $userId, 'question_id' => $questionId],
            ['bank_id' => $question->bank_id, 'content' => $content]
        );
    }

    /**
     * 试题报错
     */
    public function report(int $userId, int $questionId, array $data): void
    {
        $this->assertQuestion($questionId);

        $type = isset($data['type']) && QuestionReportType::tryFrom((int) $data['type']) !== null
            ? (int) $data['type']
            : QuestionReportType::ANSWER_WRONG->value;

        QuestionReport::create([
            'question_id' => $questionId,
            'bank_id'     => (int) (QuestionItem::whereKey($questionId)->value('bank_id') ?? 0),
            'user_id'     => $userId,
            'report_type' => $type,
            'content'     => (string) ($data['reason'] ?? ''),
            'images_json' => isset($data['images']) ? (array) $data['images'] : [],
            'status'      => 1,
        ]);
    }

    /** 组装 Question 输出（含选项 / 收藏 / 笔记） */
    private function toQuestionArray(QuestionItem $q, int $userId): array
    {
        $options = QuestionOption::where('question_id', $q->id)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('option_key')
            ->get(['option_key', 'content'])
            ->map(fn ($o) => ['key' => $o->option_key, 'content' => $o->content])
            ->all();

        $fav = UserFavoriteQuestion::where('user_id', $userId)
            ->where('question_id', $q->id)
            ->whereNull('deleted_at')
            ->exists();

        $note = UserQuestionNote::where('user_id', $userId)
            ->where('question_id', $q->id)
            ->whereNull('deleted_at')
            ->value('content');

        return [
            'id'            => $q->id,
            'bank_id'       => (int) $q->bank_id,
            'type'          => (int) $q->question_type,
            'title'         => (string) $q->stem,
            'options'       => $options,
            'answer'        => (string) $q->answer,
            'analysis'      => (string) $q->analysis,
            'is_favorited'  => $fav,
            'note'          => $note ?? '',
        ];
    }

    /** 题库可见性：本人 / 官方 / 状态正常 */
    private function assertBankVisible(int $bankId, int $userId): void
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
    }

    private function assertQuestion(int $questionId): QuestionItem
    {
        $question = QuestionItem::whereKey($questionId)->whereNull('deleted_at')->first();

        if ($question === null) {
            throw new BusinessException(ErrorCode::QUESTION_NOT_FOUND);
        }

        return $question;
    }

    private function touchDailyStat(int $userId, int $right, int $wrong, int $duration): void
    {
        $today = now()->toDateString();

        $stat = UserDailyStat::where('user_id', $userId)->where('stat_date', $today)->first();

        if ($stat === null) {
            UserDailyStat::create([
                'user_id'        => $userId,
                'stat_date'      => $today,
                'answer_count'   => 1,
                'right_count'    => $right,
                'wrong_count'    => $wrong,
                'practice_count' => 1,
                'exam_count'     => 0,
                'duration_seconds' => $duration,
            ]);

            return;
        }

        $stat->answer_count += 1;
        $stat->right_count += $right;
        $stat->wrong_count += $wrong;
        $stat->practice_count += 1;
        $stat->duration_seconds += $duration;
        $stat->save();
    }

    private function upsertWrong(int $userId, QuestionItem $question, string $userAnswer): void
    {
        $wq = UserWrongQuestion::where('user_id', $userId)
            ->where('question_id', $question->id)
            ->whereNull('deleted_at')
            ->first();

        if ($wq === null) {
            UserWrongQuestion::create([
                'user_id'      => $userId,
                'question_id'  => $question->id,
                'bank_id'      => $question->bank_id,
                'wrong_count'  => 1,
                'last_wrong_at' => now(),
                'source_type'  => 1,
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

    /**
     * 答案归一化：单选/填空/简答去空格大写；多选提取字母排序；判断统一为 A/B
     */
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
}
