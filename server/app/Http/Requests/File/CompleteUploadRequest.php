<?php

declare(strict_types=1);

namespace App\Http\Requests\File;

use Illuminate\Foundation\Http\FormRequest;

/**
 * API-FIL-002 上传完成回调登记
 */
class CompleteUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'object_key'  => ['required', 'string', 'max:500'],
            'file_hash'   => ['nullable', 'string', 'max:64'],
            'file_size'   => ['nullable', 'integer', 'min:0'],
            'origin_name' => ['nullable', 'string', 'max:255'],
            'bank_id'     => ['nullable', 'integer', 'min:0'],
            'category_id' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'object_key.required' => '缺少文件对象标识',
        ];
    }
}
