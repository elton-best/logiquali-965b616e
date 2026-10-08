<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
            'site_id' => 'required|exists:sites,id',
            'enterprise_id' => 'nullable|exists:enterprises,id',
            'send_welcome_email' => 'nullable|boolean',
            'default_role' => 'nullable|string|exists:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }
}
