<?php

declare(strict_types=1);

namespace App\Services\Console;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionSourceType;
use App\Enums\QuestionType;
use App\Exceptions\BusinessException;
use App\Models\BankChapter;
use App\Models\QuestionBank;
use App\Models\QuestionItem;
use App\Models\QuestionOption;
use App\Support\ErrorCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 题目服务（docs/04 §四 API-CSL-QST-*）
 *
 * 规则：题目归属其所属题库（bank_id），题库必须属于当前用户；
 *       增删改需同步维护 bank_question_banks.question_count 与 bank_chapters.question_count 冗余计数。
 */
class ConsoleQuestionService
{
    /**
     * 题库下题目列表
     */
    public function paginate(int $bankId, int $userId, array $filters, int $page, int $pageSize): LengthAwarePaginator
    {
        $this->assertBankOwner($bankId, $userId);

        $query = QuestionItem::with('chapter:id,name')
            ->where('bank_id', $bankId)
            ->whereNull('deleted_at');

        if (! empty($filters['keyword'])) {
            $keyword = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim((string) $filters['keyword']));
            $query->where(function ($q) use ($keyword) {
                $q->where('stem', 'like', "%{$keyword}%")
                    ->orWhere('stem_preview', 'like', "%{$keyword}%");
            });
        }
        if (! empty($filters['question_type'])) {
            $query->where('question_type', (int) $filters['question_type']);
        }
        if (! empty($filters['difficulty'])) {
            $query->where('difficulty', (int) $filters['difficulty']);
        }
        if (! empty($filters['chapter_id'])) {
            $query->where('chapter_id', (int) $filters['chapter_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', (int) $filters['status']);
        }

        return $query->orderBy('sort_order')->orderBy('id')
            ->paginate($pageSize, ['*'], 'page', $page)
            ->through(fn (QuestionItem $q) => $this->toQuestionItem($q));
    }

    /**
     * 题目详情（含选项）
     */
    public function detail(int $questionId, int $userId): array
    {
        $question = $this->assertOwner($questionId, $userId);

        $options = QuestionOption::where('question_id', $question->id)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')->orderBy('id')
            ->get(['id', 'option_key', 'content', 'is_correct', 'sort_order'])
            ->map(fn ($o) => [
                'id'         => $o->id,
                'option_key' => $o->option_key,
                'content'    => $o->content,
                'is_correct' => (int) $o->is_correct,
                'sort_order' => (int) $o->sort_order,
            ])->all();

        return $this->toQuestionItem($question, true) + ['options' => $options];
    }

    /**
     * 新增题目
     */
    public function create(int $bankId, int $userId, array $data): array
    {
        $bank = $this->assertBankOwner($bankId, $userId);

        return DB::transaction(function () use ($bank, $data) {
            $question = QuestionItem::create([
                'bank_id'       => $bank->id,
                'chapter_id'    => (int) ($data['chapter_id'] ?? 0),
                'question_type' => (int) $data['question_type'],
                'stem'          => (string) $data['stem'],
                'stem_preview'  => mb_substr(strip_tags((string) ($data['stem'] ?? '')), 0, 255),
                'analysis'      => (string) ($data['analysis'] ?? ''),
                'answer'        => (string) $data['answer'],
                'difficulty'    => (int) ($data['difficulty'] ?? QuestionDifficulty::EASY->value),
                'score'         => (float) ($data['score'] ?? 0),
                'source_type'   => QuestionSourceType::MANUAL->value,
                'status'        => 1,
                'sort_order'    => (int) ($data['sort_order'] ?? 0),
            ]);

            $this->saveOptions($question->id, $bank->id, $data['options'] ?? []);

            $bank->increment('question_count');
            if ((int) $question->chapter_id > 0) {
                BankChapter::whereKey($question->chapter_id)->increment('question_count');
            }

            return ['id' => $question->id];
        });
    }

    /**
     * 更新题目（含选项整体替换）
     */
    public function update(int $questionId, int $userId, array $data): void
    {
        $question = $this->assertOwner($questionId, $userId);
        $oldChapterId = (int) $question->chapter_id;

        DB::transaction(function () use ($question, $data, $oldChapterId) {
            $fillable = ['question_type', 'stem', 'analysis', 'answer', 'difficulty', 'score', 'chapter_id', 'sort_order'];
            foreach ($fillable as $field) {
                if (array_key_exists($field, $data)) {
                    $question->{$field} = $data[$field];
                }
            }
            if (array_key_exists('stem', $data)) {
                $question->stem_preview = mb_substr(strip_tags((string) $data['stem']), 0, 255);
            }
            $question->save();

            if (array_key_exists('options', $data)) {
                QuestionOption::where('question_id', $question->id)->delete();
                $this->saveOptions($question->id, $question->bank_id, $data['options'] ?? []);
            }

            $newChapterId = (int) $question->chapter_id;
            if ($newChapterId !== $oldChapterId) {
                if ($oldChapterId > 0) {
                    BankChapter::whereKey($oldChapterId)->decrement('question_count');
                }
                if ($newChapterId > 0) {
                    BankChapter::whereKey($newChapterId)->increment('question_count');
                }
            }
        });
    }

    /**
     * 删除题目（同步维护冗余计数）
     */
    public function delete(int $questionId, int $userId): void
    {
        $question = $this->assertOwner($questionId, $userId);
        $chapterId = (int) $question->chapter_id;
        $bankId = (int) $question->bank_id;

        DB::transaction(function () use ($question, $chapterId, $bankId) {
            QuestionOption::where('question_id', $question->id)->delete();
            $question->delete();

            QuestionBank::whereKey($bankId)->whereNull('deleted_at')
                ->whereRaw('question_count > 0')
                ->update(['question_count' => DB::raw('GREATEST(question_count - 1, 0)')]);
            if ($chapterId > 0) {
                BankChapter::whereKey($chapterId)
                    ->whereRaw('question_count > 0')
                    ->update(['question_count' => DB::raw('GREATEST(question_count - 1, 0)')]);
            }
        });
    }

    /**
     * 批量删除
     */
    public function batchDelete(int $userId, array $ids): void
    {
        $ids = array_map('intval', $ids);
        $questions = QuestionItem::with('bank')
            ->whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->get();

        DB::transaction(function () use ($questions, $userId) {
            foreach ($questions as $question) {
                if ((int) $question->bank?->user_id !== $userId) {
                    throw new BusinessException(ErrorCode::FORBIDDEN, '存在无权操作的题目');
                }
                QuestionOption::where('question_id', $question->id)->delete();
                $question->delete();

                QuestionBank::whereKey($question->bank_id)->whereNull('deleted_at')
                    ->whereRaw('question_count > 0')
                    ->update(['question_count' => DB::raw('GREATEST(question_count - 1, 0)')]);
                if ((int) $question->chapter_id > 0) {
                    BankChapter::whereKey($question->chapter_id)
                        ->whereRaw('question_count > 0')
                        ->update(['question_count' => DB::raw('GREATEST(question_count - 1, 0)')]);
                }
            }
        });
    }

    /**
     * 批量移动章节
     */
    public function batchMove(int $userId, array $ids, int $chapterId): void
    {
        $ids = array_map('intval', $ids);
        $questions = QuestionItem::with('bank')
            ->whereIn('id', $ids)
            ->whereNull('deleted_at')
            ->get();

        if ($chapterId > 0) {
            $chapter = BankChapter::whereKey($chapterId)->whereNull('deleted_at')->first();
            if ($chapter === null) {
                throw new BusinessException(ErrorCode::PARAM_ERROR, '目标章节不存在');
            }
        }

        DB::transaction(function () use ($questions, $userId, $chapterId) {
            foreach ($questions as $question) {
                if ((int) $question->bank?->user_id !== $userId) {
                    throw new BusinessException(ErrorCode::FORBIDDEN, '存在无权操作的题目');
                }
                $oldChapterId = (int) $question->chapter_id;
                if ($oldChapterId === $chapterId) {
                    continue;
                }
                $question->chapter_id = $chapterId;
                $question->save();

                if ($oldChapterId > 0) {
                    BankChapter::whereKey($oldChapterId)
                        ->whereRaw('question_count > 0')
                        ->update(['question_count' => DB::raw('GREATEST(question_count - 1, 0)')]);
                }
                if ($chapterId > 0) {
                    BankChapter::whereKey($chapterId)->increment('question_count');
                }
            }
        });
    }

    /** 题目归属校验（题目所属题库必须属于当前用户） */
    private function assertOwner(int $questionId, int $userId): QuestionItem
    {
        $question = QuestionItem::with('bank')->whereKey($questionId)->whereNull('deleted_at')->first();

        if ($question === null) {
            throw new BusinessException(ErrorCode::QUESTION_NOT_FOUND);
        }
        if ((int) $question->bank?->user_id !== $userId) {
            throw new BusinessException(ErrorCode::FORBIDDEN, '无权操作该题目');
        }

        return $question;
    }

    /** 题库归属校验 */
    private function assertBankOwner(int $bankId, int $userId): QuestionBank
    {
        $bank = QuestionBank::whereKey($bankId)->whereNull('deleted_at')->first();

        if ($bank === null) {
            throw new BusinessException(ErrorCode::BANK_NOT_FOUND);
        }
        if ((int) $bank->user_id !== $userId) {
            throw new BusinessException(ErrorCode::FORBIDDEN, '无权操作该题库');
        }

        return $bank;
    }

    /** 写入题目选项 */
    private function saveOptions(int $questionId, int $bankId, array $options): void
    {
        foreach ($options as $opt) {
            if (! is_array($opt)) {
                continue;
            }
            QuestionOption::create([
                'question_id' => $questionId,
                'bank_id'     => $bankId,
                'option_key'  => (string) ($opt['option_key'] ?? ''),
                'content'     => (string) ($opt['content'] ?? ''),
                'is_correct'  => isset($opt['is_correct']) ? (int) $opt['is_correct'] : 0,
                'sort_order'  => isset($opt['sort_order']) ? (int) $opt['sort_order'] : 0,
            ]);
        }
    }

    /** QuestionItem 输出 */
    private function toQuestionItem(QuestionItem $q, bool $withAnalysis = false): array
    {
        $item = [
            'id'               => $q->id,
            'bank_id'          => (int) $q->bank_id,
            'chapter_id'       => (int) $q->chapter_id,
            'chapter_name'     => $q->chapter?->name ?? '',
            'question_type'    => (int) $q->question_type,
            'question_type_text' => QuestionType::tryFrom((int) $q->question_type)?->label() ?? '',
            'stem'             => $q->stem,
            'stem_preview'     => $q->stem_preview,
            'answer'           => $q->answer,
            'difficulty'       => (int) $q->difficulty,
            'difficulty_text'  => QuestionDifficulty::tryFrom((int) $q->difficulty)?->label() ?? '',
            'score'            => number_format((float) $q->score, 2, '.', ''),
            'status'           => (int) $q->status,
            'source_type'      => (int) $q->source_type,
            'source_type_text' => QuestionSourceType::tryFrom((int) $q->source_type)?->label() ?? '',
            'answer_count'     => (int) $q->answer_count,
            'right_count'      => (int) $q->right_count,
            'correct_rate'     => number_format((float) $q->correct_rate, 2, '.', ''),
            'sort_order'       => (int) $q->sort_order,
            'created_at'       => $q->created_at?->toDateTimeString(),
        ];

        if ($withAnalysis) {
            $item['analysis'] = $q->analysis;
        }

        return $item;
    }
}
