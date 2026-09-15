<?php

declare(strict_types=1);

namespace App\Services\Bank;

use App\Enums\BankChargeType;
use App\Enums\BankSourceType;
use App\Enums\BankStatus;
use App\Exceptions\BusinessException;
use App\Models\BankCategory;
use App\Models\QuestionBank;
use App\Models\User;
use App\Support\ErrorCode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 题库服务（docs/04 §三 API-BANK-002 ~ 007）
 *
 * 职责：题库的增删改查与权限校验。
 * 规则：
 *   - 用户只能操作自己创建的题库（user_id = 当前用户），官方题库（user_id=0）只读
 *   - 用户上传的题库默认进入「待审核」，审核通过后其他用户才可见（详见 docs/06 §五）
 */
class QuestionBankService
{
    /**
     * 我的题库列表（分页）
     */
    public function paginateMine(int $userId, array $filters, int $page, int $pageSize): LengthAwarePaginator
    {
        $query = QuestionBank::query()
            ->where('user_id', $userId)
            ->whereNull('deleted_at');

        if (! empty($filters['keyword'])) {
            $keyword = $this->escapeLike((string) $filters['keyword']);
            $query->where('title', 'like', "%{$keyword}%");
        }

        if (! empty($filters['source_type'])) {
            $query->where('source_type', (int) $filters['source_type']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        return $query->orderByDesc('is_top')
            ->orderByDesc('updated_at')
            ->paginate($pageSize, [
                'id', 'category_id', 'title', 'subtitle', 'cover', 'source_type',
                'charge_type', 'price_amount', 'question_count', 'chapter_count',
                'practice_count', 'status', 'audit_remark', 'updated_at',
            ], 'page', $page);
    }

    /**
     * 题库市场列表（官方 + 已审核通过的公开题库）
     */
    public function paginateMarket(array $filters, int $page, int $pageSize): LengthAwarePaginator
    {
        $query = QuestionBank::query()
            ->whereIn('status', [BankStatus::NORMAL->value])
            ->whereIn('source_type', [BankSourceType::OFFICIAL->value, BankSourceType::USER_UPLOAD->value])
            ->whereNull('deleted_at');

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['keyword'])) {
            $keyword = $this->escapeLike((string) $filters['keyword']);
            $query->where('title', 'like', "%{$keyword}%");
        }

        if (! empty($filters['charge_type'])) {
            $query->where('charge_type', (int) $filters['charge_type']);
        }

        return $query->orderByDesc('is_recommend')
            ->orderByDesc('is_top')
            ->orderByDesc('practice_count')
            ->orderByDesc('id')
            ->paginate($pageSize, [
                'id', 'user_id', 'category_id', 'title', 'subtitle', 'cover',
                'source_type', 'charge_type', 'price_amount', 'question_count',
                'practice_count', 'user_count', 'tags_json', 'updated_at',
            ], 'page', $page);
    }

    /**
     * 题库详情（带归属校验）
     */
    public function detail(int $bankId, int $userId): QuestionBank
    {
        $bank = QuestionBank::with(['category:id,name,code'])
            ->whereKey($bankId)
            ->whereNull('deleted_at')
            ->first();

        if ($bank === null) {
            throw new BusinessException(ErrorCode::BANK_NOT_FOUND);
        }

        // 他人题库仅在「正常」状态可见；自己的题库在任何状态都可见（便于处理审核驳回）
        $isOwner = (int) $bank->user_id === $userId;
        $isOfficial = (int) $bank->user_id === 0;

        if (! $isOwner && ! $isOfficial && (int) $bank->status !== BankStatus::NORMAL->value) {
            throw new BusinessException(ErrorCode::BANK_NOT_FOUND);
        }

        return $bank;
    }

    /**
     * 创建题库
     *
     * 用户自建题库默认状态：待审核。审核通过前仅自己可见。
     */
    public function create(User $user, array $data): QuestionBank
    {
        $this->assertCategoryExists((int) ($data['category_id'] ?? 0));
        $this->assertOwnedBankLimit($user->id);

        return DB::transaction(function () use ($user, $data) {
            $bank = QuestionBank::create([
                'user_id'       => $user->id,
                'category_id'   => (int) ($data['category_id'] ?? 0),
                'title'         => (string) $data['title'],
                'subtitle'      => (string) ($data['subtitle'] ?? ''),
                'cover'         => (string) ($data['cover'] ?? ''),
                'source_type'   => (int) ($data['source_type'] ?? BankSourceType::USER_UPLOAD->value),
                'charge_type'   => (int) ($data['charge_type'] ?? BankChargeType::FREE->value),
                'price_amount'  => (float) ($data['price_amount'] ?? 0),
                'question_count' => 0,
                'chapter_count' => 0,
                'status'        => BankStatus::PENDING->value,
                'sort_order'    => 0,
            ]);

            $this->incrementCategoryCount((int) $bank->category_id, 1);

            return $bank;
        });
    }

    /**
     * 更新题库（仅所有者可操作；已入库的用户题库字段改动后重新进入审核）
     */
    public function update(QuestionBank $bank, int $userId, array $data): QuestionBank
    {
        $this->assertOwnership($bank, $userId);

        // 官方题库不允许普通用户编辑
        if ((int) $bank->user_id === 0) {
            throw new BusinessException(ErrorCode::BANK_NO_PERMISSION, '官方题库不可编辑');
        }

        if (isset($data['category_id'])) {
            $this->assertCategoryExists((int) $data['category_id']);
        }

        return DB::transaction(function () use ($bank, $data) {
            $oldCategoryId = (int) $bank->category_id;

            $fillable = ['title', 'subtitle', 'cover', 'category_id', 'charge_type', 'price_amount'];

            foreach ($fillable as $field) {
                if (array_key_exists($field, $data)) {
                    $bank->{$field} = $data[$field];
                }
            }

            // 标题或封面等对外展示字段变更 → 重新走审核
            if ((int) $bank->status === BankStatus::REJECTED->value) {
                $bank->status = BankStatus::PENDING->value;
                $bank->audit_remark = '';
            }

            $bank->save();

            if ((int) $bank->category_id !== $oldCategoryId) {
                $this->incrementCategoryCount($oldCategoryId, -1);
                $this->incrementCategoryCount((int) $bank->category_id, 1);
            }

            return $bank;
        });
    }

    /**
     * 删除题库（软删；题目与章节一并软删）
     */
    public function delete(QuestionBank $bank, int $userId): void
    {
        $this->assertOwnership($bank, $userId);

        if ((int) $bank->user_id === 0) {
            throw new BusinessException(ErrorCode::BANK_NO_PERMISSION, '官方题库不可删除');
        }

        DB::transaction(function () use ($bank) {
            $bank->questions()->delete();
            $bank->chapters()->delete();

            $categoryId = (int) $bank->category_id;
            $count = (int) $bank->question_count;

            $bank->delete();

            $this->incrementCategoryCount($categoryId, -1);

            // 题库删除后释放其题目所占配额（计数由 data:recount 兜底校准）
            if ($count > 0) {
                DB::table('bank_categories')
                    ->where('id', $categoryId)
                    ->update(['updated_at' => now()]);
            }
        });
    }

    /** 归属校验 */
    public function assertOwnership(QuestionBank $bank, int $userId): void
    {
        if ((int) $bank->user_id !== $userId) {
            throw new BusinessException(ErrorCode::BANK_NO_PERMISSION);
        }
    }

    private function assertCategoryExists(int $categoryId): void
    {
        if ($categoryId <= 0) {
            throw new BusinessException(ErrorCode::PARAM_ERROR, '请选择题库分类');
        }

        $exists = BankCategory::whereKey($categoryId)->where('status', 1)->exists();

        if (! $exists) {
            throw new BusinessException(ErrorCode::PARAM_ERROR, '题库分类不存在或已停用');
        }
    }

    private function assertOwnedBankLimit(int $userId): void
    {
        $max = (int) config('anbiguo.bank.max_per_user', 200);

        $count = QuestionBank::where('user_id', $userId)->count();

        if ($count >= $max) {
            throw new BusinessException(
                ErrorCode::QUESTION_LIMIT,
                "单个账号最多创建 {$max} 个题库，请先清理不再使用的题库"
            );
        }
    }

    /** 维护分类下题库数（冗余计数，容错处理，失败不影响主流程） */
    private function incrementCategoryCount(int $categoryId, int $delta): void
    {
        if ($categoryId <= 0) {
            return;
        }

        BankCategory::whereKey($categoryId)
            ->whereRaw('question_bank_count + ? >= 0', [$delta])
            ->update([
                'question_bank_count' => DB::raw('question_bank_count + '.($delta >= 0 ? 1 : -1)),
                'updated_at'          => now(),
            ]);
    }

    /** 转义 LIKE 通配符，防止用户输入 % 拖库 */
    private function escapeLike(string $keyword): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim($keyword));
    }
}
