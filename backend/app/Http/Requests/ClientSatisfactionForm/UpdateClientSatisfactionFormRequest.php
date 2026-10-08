<?php

namespace App\Http\Requests\ClientSatisfactionForm;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientSatisfactionFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_id' => ['sometimes', 'exists:sites,id'],
            'client_name' => ['sometimes', 'string', 'max:255'],
            'survey_date' => ['sometimes', 'date'],
            'amabilite_ecoute' => ['sometimes', 'integer', 'min:1', 'max:4'],
            'disponibilite_spontaneite' => ['sometimes', 'integer', 'min:1', 'max:4'],
            'rapidite_traitement' => ['sometimes', 'integer', 'min:1', 'max:4'],
            'respect_delais' => ['sometimes', 'integer', 'min:1', 'max:4'],
            'conformite_produits' => ['sometimes', 'integer', 'min:1', 'max:4'],
            'traitement_reclamations' => ['sometimes', 'integer', 'min:1', 'max:4'],
            'recommendations' => ['nullable', 'string', 'max:2000'],
            'm13_d4_traceability' => ['nullable', 'array'],
            'm13_d6_traceability' => ['nullable', 'array'],
            'status' => ['sometimes', 'in:draft,submitted'],
        ];
    }
}
