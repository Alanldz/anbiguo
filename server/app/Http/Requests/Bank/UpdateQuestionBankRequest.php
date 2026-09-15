<?php

declare(strict_types=1);

namespace App\Http\Requests\Bank;

use App\Enums\BankChargeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * API-BANK-005 更新 / 重命名题库
 *
 * 全部字段均为「可选」，只提交需要修改的字段（PATCH 语义的 PUT）。
 */
class UpdateQuestionBankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'        => ['sometimes', 'required', 'string', 'min:1', 'max:120'],
            'subtitle'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'cover'        => ['sometimes', 'nullable', 'string', 'max:255'],
            'category_id'  => ['sometimes', 'integer', 'min:1'],
            'charge_type'  => ['sometimes', 'integer', Rule::in(array_column(BankChargeType::cases(), 'value'))],
            'price_amount' => ['sometimes', 'numeric', 'min:0', 'max:99999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => '题库名称不能为空',
            'title.max'      => '题库名称最多 120 个字',
        ];
    }
}
