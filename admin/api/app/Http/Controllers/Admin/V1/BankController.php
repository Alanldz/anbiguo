<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Models\QuestionBank;
use App\Models\SysAdmin;
use App\Support\ApiResponse;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 题库管理（docs/04 §五 API-ADM-BANK-001 / API-ADM-BANK-002）
 *   API-ADM-070 GET  /banks            题库列表（含所属用户手机号）
 *   API-ADM-071 PUT  /banks/{id}/audit 审核通过/拒绝（status 1|4）
 *   API-ADM-072 PUT  /banks/{id}/status 上架/隐藏（status 1|2）
 */
class BankController extends Controller
{
    /** API-ADM-070 题库列表（含所属用户手机号） */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $keyword = trim((string) $request->query('keyword', ''));
        $page = $this->pageParams();

        $query = QuestionBank::query()->with('owner:id,mobile');

        if (is_numeric($status) && (int) $status > 0) {
            $query->where('status', (int) $status);
        }
        if ($keyword !== '') {
            $query->where('title', 'like', "%{$keyword}%");
        }

        $paginator = $query->orderByDesc('id')
            ->paginate($page['page_size'], ['*'], 'page', $page['page']);

        $list = collect($paginator->items())->map(function (QuestionBank $bank) {
            $arr = $bank->toArray();
            $arr['owner_mobile'] = $bank->owner?->mobile ?? '';

            return $arr;
        })->all();

        return ApiResponse::success([
            'list' => $list,
            'pagination' => [
                'page'        => $paginator->currentPage(),
                'page_size'   => $paginator->perPage(),
                'total'       => $paginator->total(),
                'total_pages' => $paginator->lastPage(),
            ],
        ]);
    }

    /** API-ADM-071 内容审核（通过 status=1 / 拒绝 status=4） */
    public function audit(Request $request, int $id): JsonResponse
    {
        /** @var QuestionBank|null $bank */
        $bank = QuestionBank::find($id);
        if ($bank === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '题库不存在', null, 404);
        }

        $data = $request->validate([
            'status' => ['required', 'integer', 'in:1,4'],
            'remark' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var SysAdmin $admin */
        $admin = $request->user();

        $bank->update([
            'status'       => $data['status'],
            'audited_by'   => $admin->id,
            'audited_at'   => now(),
            'audit_remark' => $data['remark'] ?? '',
        ]);

        return ApiResponse::success($bank->toArray(), $data['status'] === QuestionBank::AUDIT_PASS ? '审核通过' : '已拒绝');
    }

    /** API-ADM-072 上架/隐藏（status 1|2） */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        /** @var QuestionBank|null $bank */
        $bank = QuestionBank::find($id);
        if ($bank === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '题库不存在', null, 404);
        }

        $data = $request->validate([
            'status' => ['required', 'integer', 'in:1,2'],
        ]);

        // 被拒绝的题库不允许直接上架（需重新审核）
        if ($data['status'] === QuestionBank::VISIBLE_ON
            && $bank->status === QuestionBank::STATUS_REJECTED) {
            throw new BusinessException(ErrorCode::BANK_AUDIT_REJECTED, '该题库已被拒绝，无法上架', null, 403);
        }

        $bank->update(['status' => $data['status']]);

        return ApiResponse::success($bank->toArray(), $data['status'] === QuestionBank::VISIBLE_ON ? '已上架' : '已隐藏');
    }
}
