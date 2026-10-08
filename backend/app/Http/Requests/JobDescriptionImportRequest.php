<?php

namespace App\Http\Requests;

use App\Models\JobDescription;
use App\Services\UploadSecurityService;
use Illuminate\Foundation\Http\FormRequest;

class JobDescriptionImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Autorisation basée sur permissions (pas de policy dédiée)
        return $this->user()?->can('job_descriptions.create') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:51200', // 50 MB
            ],
            'import_mode' => [
                'required',
                'in:strict,flexible',
            ],
        ];
    }

    /**
     * Préparer les données pour validation.
     * Valider la sécurité du fichier avant la validation des règles.
     */
    protected function prepareForValidation(): void
    {
        $file = $this->file('file');
        
        if ($file) {
            try {
                app(UploadSecurityService::class)->validateSpreadsheet($file);
            } catch (\RuntimeException $e) {
                abort(422, $e->getMessage());
            }
        }
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Veuillez sélectionner un fichier à importer.',
            'file.file' => 'Le fichier uploadé est invalide.',
            'file.mimes' => 'Le fichier doit être au format Excel (.xlsx, .xls) ou CSV.',
            'file.max' => 'Le fichier ne doit pas dépasser 50 MB.',
            'import_mode.required' => 'Le mode d\'import est requis.',
            'import_mode.in' => 'Le mode d\'import doit être "strict" ou "flexible".',
        ];
    }
}
