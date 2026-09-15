<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\BankChargeType;
use App\Enums\BankSourceType;
use App\Enums\BankStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 题库资源（统一出参结构，避免各接口字段不一致）
 *
 * @mixin \App\Models\QuestionBank
 */
class QuestionBankResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sourceType = BankSourceType::tryFrom((int) $this->source_type);
        $status = BankStatus::tryFrom((int) $this->status);
        $chargeType = BankChargeType::tryFrom((int) $this->charge_type);

        return [
            'id'             => $this->id,
            'user_id'        => (int) $this->user_id,
            'is_official'    => (int) $this->user_id === 0,
            'category_id'    => (int) $this->category_id,
            'category_name'  => $this->whenLoaded('category', fn () => $this->category?->name, ''),
            'title'          => $this->title,
            'subtitle'       => $this->subtitle,
            'cover'          => $this->cover,

            'source_type'    => (int) $this->source_type,
            'source_text'    => $sourceType?->label() ?? '',

            'charge_type'    => (int) $this->charge_type,
            'charge_text'    => $chargeType?->label() ?? '',
            'price_amount'   => (float) $this->price_amount,

            'question_count' => (int) $this->question_count,
            'chapter_count'  => (int) $this->chapter_count,
            'practice_count' => (int) $this->practice_count,
            'user_count'     => (int) $this->user_count,

            'tags'           => $this->tags_json ?? [],
            'is_top'         => (bool) $this->is_top,
            'is_recommend'   => (bool) $this->is_recommend,

            'status'         => (int) $this->status,
            'status_text'    => $status?->label() ?? '',
            'audit_remark'   => $this->when(
                (int) $this->status === BankStatus::REJECTED->value,
                fn () => $this->audit_remark
            ),

            'created_at'     => $this->created_at?->toDateTimeString(),
            'updated_at'     => $this->updated_at?->toDateTimeString(),
        ];
    }
}
