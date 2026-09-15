<?php

declare(strict_types=1);

namespace App\Http\Requests\File;

use App\Enums\FileBizType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * API-FIL-001 获取 OSS 直传凭证
 */
class UploadTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'biz_type'   => ['required', 'integer', Rule::in(array_column(FileBizType::cases(), 'value'))],
            'ext'        => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'],
            'size'       => ['required', 'integer', 'min:1'],
            'bank_id'    => ['nullable', 'integer', 'min:0'],
            'question_id' => ['nullable', 'integer', 'min:0'],
            'category_id' => ['nullable', 'integer', 'min:0'],
            'origin_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'biz_type.required' => '缺少文件业务类型',
            'ext.required'      => '缺少文件扩展名',
            'size.required'     => '缺少文件大小',
        ];
    }
}
