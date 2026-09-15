<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * API-AUTH-002 手机号登录 / 注册
 */
class MobileLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mobile' => ['required', 'string', 'regex:/^1[3-9]\d{9}$/'],
            'code'   => ['required', 'string', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.required' => '请输入手机号',
            'mobile.regex'    => '手机号格式不正确',
            'code.required'   => '请输入验证码',
            'code.digits'     => '验证码为 6 位数字',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mobile' => trim((string) $this->input('mobile')),
            'code'   => trim((string) $this->input('code')),
        ]);
    }
}
