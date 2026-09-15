<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * API-AUTH-001 发送短信验证码
 */
class SendSmsCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mobile' => ['required', 'string', 'regex:/^1[3-9]\d{9}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.required' => '请输入手机号',
            'mobile.regex'    => '手机号格式不正确',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mobile' => trim((string) $this->input('mobile')),
        ]);
    }
}
