<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('documents.create');
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:5120', // 5MB
            ],
            'site_id' => [
                'required',
                'integer',
                'exists:sites,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Le fichier XLSX est requis',
            'file.mimes' => 'Le fichier doit être au format XLSX ou XLS',
            'file.max' => 'Le fichier ne doit pas dépasser 5 Mo',
            'site_id.required' => 'Le site est requis',
            'site_id.exists' => 'Le site spécifié n\'existe pas',
        ];
    }
}
