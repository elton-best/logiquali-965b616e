<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiGenerateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prompt' => 'required|string|min:3|max:6000',
            'system' => 'nullable|string|max:2000',
            'model' => 'nullable|string|max:100',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:1|max:4096',
        ];
    }
}
