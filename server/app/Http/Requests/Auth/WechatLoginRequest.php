<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * API-AUTH-003 微信小程序登录
 */
class WechatLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => '缺少微信登录凭证',
        ];
    }
}
