<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Exceptions\BusinessException;
use App\Models\MemberPlan;
use App\Support\ErrorCode;

/**
 * 会员套餐配置服务（docs/04 §五 API-ADM-105）
 *
 * GET 全量（按 sort_order 升序），benefits_json 解析为数组返回。
 * PUT 任意子集（benefits 数组由后端 json 编码存储）。
 */
class MemberPlanService
{
    /** 全量列表（按 sort_order 升序） */
    public function listAll(): array
    {
        $rows = MemberPlan::query()->orderBy('sort_order')->orderBy('id')->get();

        return $rows->map(fn (MemberPlan $p) => $this->toRow($p))->all();
    }

    /** 编辑套餐（任意子集） */
    public function update(int $id, array $data): array
    {
        /** @var MemberPlan|null $plan */
        $plan = MemberPlan::find($id);
        if ($plan === null) {
            throw new BusinessException(ErrorCode::DATA_NOT_FOUND, '套餐不存在', null, 404);
        }

        $attributes = [];
        foreach (['name', 'level', 'duration_days', 'price_amount', 'origin_amount', 'description', 'ai_import_quota', 'is_recommend', 'sort_order', 'status'] as $field) {
            if (array_key_exists($field, $data)) {
                $attributes[$field] = $data[$field];
            }
        }
        // benefits 数组 → JSON 存储
        if (array_key_exists('benefits', $data)) {
            $attributes['benefits_json'] = json_encode(array_values((array) $data['benefits']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($attributes !== []) {
            $plan->update($attributes);
        }

        return $this->toRow($plan->fresh());
    }

    /** 对外行结构（benefits 解析为数组） */
    public function toRow(MemberPlan $plan): array
    {
        $benefits = [];
        if (! empty($plan->benefits_json)) {
            $decoded = json_decode($plan->benefits_json, true);
            $benefits = is_array($decoded) ? $decoded : [];
        }

        return [
            'id'             => $plan->id,
            'name'           => $plan->name,
            'level'          => (int) $plan->level,
            'duration_days'  => (int) $plan->duration_days,
            'price_amount'   => $plan->price_amount,
            'origin_amount'  => $plan->origin_amount,
            'description'    => $plan->description,
            'benefits'       => $benefits,
            'ai_import_quota'=> (int) $plan->ai_import_quota,
            'is_recommend'   => (int) $plan->is_recommend,
            'sort_order'     => (int) $plan->sort_order,
            'status'         => (int) $plan->status,
            'created_at'     => $plan->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
