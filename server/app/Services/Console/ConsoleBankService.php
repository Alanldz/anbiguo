<?php

declare(strict_types=1);

namespace App\Services\Console;

use App\Enums\BankChargeType;
use App\Enums\BankSourceType;
use App\Enums\BankStatus;
use App\Exceptions\BusinessException;
use App\Models\BankCategory;
use App\Models\BankChapter;
use App\Models\QuestionBank;
use App\Models\QuestionItem;
use App\Models\User;
use App\Support\ErrorCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 题库服务（docs/04 §四 API-CSL-BANK-*、API-CSL-CHP-*）
 *
 * 规则：用户仅能操作自己创建的题库（user_id = 当前用户）；删除级联软删题目与章节。
 */
class ConsoleBankService
{
    /**
     * 我的题库列表（分页）
     */
    public function paginateMine(int $userId, array $filters, int $page, int $pageSize): LengthAwarePaginator
    {
        $query = QuestionBank::with('category:id,name')
            ->where('user_id', $userId)
            ->whereNull('deleted_at');

        if (! empty($filters['keyword'])) {
            $keyword = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim((string) $filters['keyword']));
            $query->where('title', 'like', "%{$keyword}%");
        }
        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }
        if (! empty($filters['source_type'])) {
            $query->where('source_type', (int) $filters['source_type']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', (int) $filters['status']);
        }

        return $query->orderByDesc('updated_at')
            ->paginate($pageSize, ['*'], 'page', $page)
            ->through(fn (QuestionBank $bank) => $this->toBankItem($bank));
    }

    /**
     * 题库详情（带归属校验）
     */
    public function detail(int $bankId, int $userId): array
    {
        $bank = QuestionBank::with('category:id,name')
            ->whereKey($bankId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if ($bank === null) {
            throw new BusinessException(ErrorCode::BANK_NOT_FOUND);
        }

        return $this->toBankItem($bank);
    }

    /**
     * 新建题库
     */
    public function create(User $user, array $data): array
    {
        if (! empty($data['category_id'])) {
            $this->assertCategoryExists((int) $data['category_id']);
        }

        $bank = QuestionBank::create([
            'user_id'      => $user->id,
            'category_id'  => (int) ($data['category_id'] ?? 0),
            'title'        => (string) $data['title'],
            'subtitle'     => (string) ($data['subtitle'] ?? ''),
            'cover'        => (string) ($data['cover'] ?? ''),
            'source_type'  => BankSourceType::USER_UPLOAD->value,
            'charge_type'  => (int) ($data['charge_type'] ?? BankChargeType::FREE->value),
            'question_count' => 0,
            'chapter_count'  => 0,
            'status'         => BankStatus::PENDING->value,
            'sort_order'     => 0,
        ]);

        $this->incrementCategoryCount((int) $bank->category_id, 1);

        return ['id' => $bank->id];
    }

    /**
     * 更新 / 重命名题库
     */
    public function update(int $bankId, int $userId, array $data): void
    {
        $bank = $this->assertOwned($bankId, $userId);

        if (isset($data['category_id'])) {
            $this->assertCategoryExists((int) $data['category_id']);
        }

        DB::transaction(function () use ($bank, $data) {
            $oldCategoryId = (int) $bank->category_id;

            $fillable = ['title', 'subtitle', 'cover', 'category_id', 'charge_type'];
            foreach ($fillable as $field) {
                if (array_key_exists($field, $data)) {
                    $bank->{$field} = $data[$field];
                }
            }
            $bank->save();

            $newCategoryId = (int) $bank->category_id;
            if ($newCategoryId !== $oldCategoryId) {
                $this->incrementCategoryCount($oldCategoryId, -1);
                $this->incrementCategoryCount($newCategoryId, 1);
            }
        });
    }

    /**
     * 删除题库（级联软删题目与章节）
     */
    public function delete(int $bankId, int $userId): void
    {
        $bank = $this->assertOwned($bankId, $userId);

        DB::transaction(function () use ($bank) {
            $questionIds = QuestionItem::where('bank_id', $bank->id)->whereNull('deleted_at')
                ->pluck('id')->all();
            if ($questionIds !== []) {
                QuestionItem::whereIn('id', $questionIds)->delete();
                \App\Models\QuestionOption::whereIn('question_id', $questionIds)->delete();
            }

            BankChapter::where('bank_id', $bank->id)->whereNull('deleted_at')->delete();

            $categoryId = (int) $bank->category_id;
            $bank->delete();

            $this->incrementCategoryCount($categoryId, -1);
        });
    }

    /**
     * 导出题库（返回契约 JSON 结构，不做服务端文件生成）
     */
    public function export(int $bankId, int $userId): array
    {
        $bank = $this->assertOwned($bankId, $userId);

        $questions = QuestionItem::with('options:id,question_id,option_key,content,is_correct,sort_order')
            ->where('bank_id', $bank->id)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $questionList = $questions->map(function (QuestionItem $q) {
            return [
                'question_type' => (int) $q->question_type,
                'stem'          => $q->stem,
                'analysis'      => $q->analysis,
                'answer'        => $q->answer,
                'difficulty'    => (int) $q->difficulty,
                'score'         => number_format((float) $q->score, 2, '.', ''),
                'options'       => $q->options->map(fn ($o) => [
                    'option_key' => $o->option_key,
                    'content'    => $o->content,
                    'is_correct' => (int) $o->is_correct,
                ])->all(),
            ];
        })->all();

        return [
            'bank' => [
                'id'    => $bank->id,
                'title' => $bank->title,
            ],
            'exported_at' => now()->toDateTimeString(),
            'questions'   => $questionList,
        ];
    }

    /**
     * 题库分类树（下拉用，系统分类 user_id=0）
     */
    public function categoryTree(): array
    {
        $categories = BankCategory::query()
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'parent_id', 'name', 'code', 'level'])
            ->all();

        $map = [];
        foreach ($categories as $cat) {
            $map[$cat->id] = [
                'id'       => $cat->id,
                'parent_id' => (int) $cat->parent_id,
                'name'     => $cat->name,
                'code'     => $cat->code,
                'level'    => (int) $cat->level,
                'children' => [],
            ];
        }

        $tree = [];
        foreach ($map as $id => &$node) {
            $pid = $node['parent_id'];
            if ($pid > 0 && isset($map[$pid])) {
                $map[$pid]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }
        unset($node);

        return $tree;
    }

    /** BankItem 输出 */
    public function toBankItem(QuestionBank $bank): array
    {
        return [
            'id'             => $bank->id,
            'title'          => $bank->title,
            'subtitle'       => $bank->subtitle,
            'cover'          => $bank->cover,
            'category_id'    => (int) $bank->category_id,
            'category_name'  => $bank->category?->name ?? '',
            'source_type'    => (int) $bank->source_type,
            'source_type_text' => BankSourceType::tryFrom((int) $bank->source_type)?->label() ?? '',
            'charge_type'    => (int) $bank->charge_type,
            'charge_type_text' => BankChargeType::tryFrom((int) $bank->charge_type)?->label() ?? '',
            'question_count' => (int) $bank->question_count,
            'chapter_count'  => (int) $bank->chapter_count,
            'practice_count' => (int) $bank->practice_count,
            'user_count'     => (int) $bank->user_count,
            'status'         => (int) $bank->status,
            'status_text'    => BankStatus::tryFrom((int) $bank->status)?->label() ?? '',
            'tags'           => is_array($bank->tags_json) ? $bank->tags_json : [],
            'created_at'     => $bank->created_at?->toDateTimeString(),
            'updated_at'     => $bank->updated_at?->toDateTimeString(),
        ];
    }

    // =========================================================================
    // 章节 CRUD（API-CSL-CHP-*）
    // =========================================================================

    /**
     * 题库下章节列表（扁平数组）
     */
    public function chapters(int $bankId, int $userId): array
    {
        $this->assertOwned($bankId, $userId);

        return BankChapter::where('bank_id', $bankId)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'bank_id', 'parent_id', 'name', 'level', 'question_count', 'sort_order'])
            ->map(fn (BankChapter $c) => $this->toChapterItem($c))
            ->all();
    }

    /**
     * 新建章节
     */
    public function createChapter(int $bankId, int $userId, array $data): array
    {
        $bank = $this->assertOwned($bankId, $userId);

        $parentId = (int) ($data['parent_id'] ?? 0);
        $level = 1;
        if ($parentId > 0) {
            $parent = BankChapter::whereKey($parentId)
                ->where('bank_id', $bank->id)
                ->whereNull('deleted_at')
                ->first();
            if ($parent === null) {
                throw new BusinessException(ErrorCode::PARAM_ERROR, '父章节不存在');
            }
            $level = min((int) $parent->level + 1, 2);
        }

        $chapter = BankChapter::create([
            'bank_id'     => $bank->id,
            'parent_id'   => $parentId,
            'name'        => (string) $data['name'],
            'level'       => $level,
            'question_count' => 0,
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
        ]);

        $bank->increment('chapter_count');

        return ['id' => $chapter->id];
    }

    /**
     * 更新章节
     */
    public function updateChapter(int $chapterId, int $userId, array $data): void
    {
        $chapter = $this->assertChapterOwner($chapterId, $userId);

        if (isset($data['name'])) {
            $chapter->name = (string) $data['name'];
        }
        if (isset($data['sort_order'])) {
            $chapter->sort_order = (int) $data['sort_order'];
        }
        $chapter->save();
    }

    /**
     * 删除章节（该章节下题目 chapter_id 置 0）
     */
    public function deleteChapter(int $chapterId, int $userId): void
    {
        $chapter = $this->assertChapterOwner($chapterId, $userId);
        $bankId = (int) $chapter->bank_id;

        DB::transaction(function () use ($chapter, $bankId) {
            QuestionItem::where('chapter_id', $chapter->id)->whereNull('deleted_at')
                ->update(['chapter_id' => 0]);
            $chapter->delete();

            QuestionBank::whereKey($bankId)->whereNull('deleted_at')
                ->whereRaw('chapter_count > 0')
                ->update(['chapter_count' => DB::raw('GREATEST(chapter_count - 1, 0)')]);
        });
    }

    /** ChapterItem 输出 */
    private function toChapterItem(BankChapter $c): array
    {
        return [
            'id'             => $c->id,
            'bank_id'        => (int) $c->bank_id,
            'parent_id'      => (int) $c->parent_id,
            'name'           => $c->name,
            'level'          => (int) $c->level,
            'question_count' => (int) $c->question_count,
            'sort_order'     => (int) $c->sort_order,
        ];
    }

    /** 章节归属校验（所属题库必须属于当前用户） */
    private function assertChapterOwner(int $chapterId, int $userId): BankChapter
    {
        $chapter = BankChapter::with('bank')
            ->whereKey($chapterId)
            ->whereNull('deleted_at')
            ->first();

        if ($chapter === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '章节不存在');
        }
        if ((int) $chapter->bank?->user_id !== $userId) {
            throw new BusinessException(ErrorCode::FORBIDDEN, '无权操作该章节');
        }

        return $chapter;
    }

    /** 归属校验 */
    public function assertOwned(int $bankId, int $userId): QuestionBank
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

    private function assertCategoryExists(int $categoryId): void
    {
        if ($categoryId <= 0) {
            throw new BusinessException(ErrorCode::PARAM_ERROR, '请选择题库分类');
        }
        $exists = BankCategory::whereKey($categoryId)->where('status', 1)->whereNull('deleted_at')->exists();
        if (! $exists) {
            throw new BusinessException(ErrorCode::PARAM_ERROR, '题库分类不存在或已停用');
        }
    }

    private function incrementCategoryCount(int $categoryId, int $delta): void
    {
        if ($categoryId <= 0) {
            return;
        }
        BankCategory::whereKey($categoryId)
            ->whereRaw('question_bank_count + ? >= 0', [$delta])
            ->update([
                'question_bank_count' => DB::raw('GREATEST(question_bank_count + '.($delta >= 0 ? 1 : -1).', 0)'),
                'updated_at'          => now(),
            ]);
    }
}
