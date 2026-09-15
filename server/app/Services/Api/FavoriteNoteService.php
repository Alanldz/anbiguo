<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\QuestionOption;
use App\Models\UserFavoriteQuestion;
use App\Models\UserQuestionNote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 收藏 / 笔记列表服务
 * 台账：docs/04-API接口规范与登记表.md §二 API-FAV-001、API-NOTE-001
 *
 * 列表项取自用户私有数据（user_favorite_questions / user_question_notes），
 * 关联 question_items 取题干/题型/难度，关联 bank_question_banks 取题库名。
 * 所有查询强制 user_id 边界，避免越权读取他人数据。
 */
class FavoriteNoteService
{
    /**
     * 我的收藏列表（分页）
     */
    public function favorites(int $userId, array $filters, int $page, int $pageSize): LengthAwarePaginator
    {
        $query = UserFavoriteQuestion::query()
            ->where('user_favorite_questions.user_id', $userId)
            ->whereNull('user_favorite_questions.deleted_at')
            ->join('question_items', 'question_items.id', '=', 'user_favorite_questions.question_id')
            ->leftJoin('bank_question_banks', 'bank_question_banks.id', '=', 'user_favorite_questions.bank_id')
            ->select(
                'user_favorite_questions.id as fav_id',
                'user_favorite_questions.question_id',
                'user_favorite_questions.bank_id',
                'user_favorite_questions.folder_name',
                'user_favorite_questions.created_at as fav_created_at',
                'question_items.stem',
                'question_items.question_type',
                'question_items.difficulty',
                'bank_question_banks.title as bank_name'
            );

        if (! empty($filters['bank_id'])) {
            $query->where('user_favorite_questions.bank_id', (int) $filters['bank_id']);
        }

        if (! empty($filters['keyword'])) {
            // 题干模糊匹配，转义 %/_ 防拖库
            $keyword = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim((string) $filters['keyword']));
            $query->where('question_items.stem', 'like', "%{$keyword}%");
        }

        return $query->orderByDesc('user_favorite_questions.created_at')
            ->orderByDesc('user_favorite_questions.id')
            ->paginate($pageSize, ['*'], 'page', $page)
            ->through(function ($row) {
                /** @var \stdClass $row */
                $questionId = (int) $row->question_id;

                return [
                    'id'                  => (int) $row->fav_id,
                    'question_id'         => $questionId,
                    'bank_id'             => (int) $row->bank_id,
                    'bank_name'           => (string) ($row->bank_name ?? ''),
                    'question_type'       => (int) $row->question_type,
                    'question_title'      => (string) ($row->stem ?? ''),
                    'question_options'    => $this->options($questionId),
                    'question_difficulty' => (int) ($row->difficulty ?? 0),
                    'folder_name'         => (string) ($row->folder_name ?? ''),
                    'created_at'          => $row->fav_created_at ? (string) $row->fav_created_at : null,
                ];
            });
    }

    /**
     * 我的笔记列表（分页）
     */
    public function notes(int $userId, array $filters, int $page, int $pageSize): LengthAwarePaginator
    {
        $query = UserQuestionNote::query()
            ->where('user_question_notes.user_id', $userId)
            ->whereNull('user_question_notes.deleted_at')
            ->join('question_items', 'question_items.id', '=', 'user_question_notes.question_id')
            ->leftJoin('bank_question_banks', 'bank_question_banks.id', '=', 'user_question_notes.bank_id')
            ->select(
                'user_question_notes.id as note_id',
                'user_question_notes.question_id',
                'user_question_notes.bank_id',
                'user_question_notes.content',
                'user_question_notes.like_count',
                'user_question_notes.created_at as note_created_at',
                'user_question_notes.updated_at as note_updated_at',
                'question_items.stem',
                'bank_question_banks.title as bank_name'
            );

        if (! empty($filters['bank_id'])) {
            $query->where('user_question_notes.bank_id', (int) $filters['bank_id']);
        }

        if (! empty($filters['keyword'])) {
            // content 模糊匹配，转义 %/_ 防拖库
            $keyword = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim((string) $filters['keyword']));
            $query->where('user_question_notes.content', 'like', "%{$keyword}%");
        }

        return $query->orderByDesc('user_question_notes.created_at')
            ->orderByDesc('user_question_notes.id')
            ->paginate($pageSize, ['*'], 'page', $page)
            ->through(function ($row) {
                /** @var \stdClass $row */
                return [
                    'id'           => (int) $row->note_id,
                    'question_id'  => (int) $row->question_id,
                    'bank_id'      => (int) $row->bank_id,
                    'bank_name'    => (string) ($row->bank_name ?? ''),
                    'question_title' => (string) ($row->stem ?? ''),
                    'content'      => (string) ($row->content ?? ''),
                    'like_count'   => (int) ($row->like_count ?? 0),
                    'created_at'   => $row->note_created_at ? (string) $row->note_created_at : null,
                    'updated_at'   => $row->note_updated_at ? (string) $row->note_updated_at : null,
                ];
            });
    }

    /** 题目选项（与既有 QUE-001 / WRG-001 取选项方式保持一致） */
    private function options(int $questionId): array
    {
        return QuestionOption::where('question_id', $questionId)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('option_key')
            ->get(['option_key', 'content'])
            ->map(fn ($o) => ['key' => $o->option_key, 'content' => $o->content])
            ->all();
    }
}
