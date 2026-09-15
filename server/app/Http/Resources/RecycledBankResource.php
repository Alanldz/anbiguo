<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 回收站题库资源（API-BANK-008）
 *
 * 在题库列表项（QuestionBankResource）基础上追加 deleted_at（Y-m-d H:i:s）。
 * 其余字段与「我的题库列表」完全一致，便于客户端复用列表渲染。
 *
 * @mixin \App\Models\QuestionBank
 */
class RecycledBankResource extends QuestionBankResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'deleted_at' => $this->deleted_at?->toDateTimeString(),
        ]);
    }
}
