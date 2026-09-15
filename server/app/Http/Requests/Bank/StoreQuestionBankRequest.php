<?php

declare(strict_types=1);

namespace App\Http\Requests\Bank;

use App\Enums\BankChargeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * API-BANK-004 创建题库
 */
class StoreQuestionBankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'min:1', 'max:120'],
            'subtitle'     => ['nullable', 'string', 'max:255'],
            'cover'        => ['nullable', 'string', 'max:255'],
            'category_id'  => ['required', 'integer', 'min:1'],
            'charge_type'  => ['nullable', 'integer', Rule::in(array_column(BankChargeType::cases(), 'value'))],
            // 单独购买时必须给出大于 0 的价格
            'price_amount' => ['nullable', 'numeric', 'min:0', 'max:99999.99', 'required_if:charge_type,'.BankChargeType::PURCHASE->value],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'       => '请填写题库名称',
            'title.max'            => '题库名称最多 120 个字',
            'category_id.required' => '请选择题库分类',
            'price_amount.required_if' => '单独购买的题库必须设置价格',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title'    => trim((string) $this->input('title')),
            'subtitle' => trim((string) $this->input('subtitle', '')),
        ]);
    }
}
