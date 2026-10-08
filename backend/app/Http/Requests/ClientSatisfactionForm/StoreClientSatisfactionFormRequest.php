<?php

namespace App\Http\Requests\ClientSatisfactionForm;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientSatisfactionFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_id' => ['required', 'exists:sites,id'],
            'client_name' => ['required', 'string', 'max:255'],
            'survey_date' => ['required', 'date'],
            'amabilite_ecoute' => ['required', 'integer', 'min:1', 'max:4'],
            'disponibilite_spontaneite' => ['required', 'integer', 'min:1', 'max:4'],
            'rapidite_traitement' => ['required', 'integer', 'min:1', 'max:4'],
            'respect_delais' => ['required', 'integer', 'min:1', 'max:4'],
            'conformite_produits' => ['required', 'integer', 'min:1', 'max:4'],
            'traitement_reclamations' => ['required', 'integer', 'min:1', 'max:4'],
            'recommendations' => ['nullable', 'string', 'max:2000'],
            'm13_d4_traceability' => ['nullable', 'array'],
            'm13_d6_traceability' => ['nullable', 'array'],
            'status' => ['sometimes', 'in:draft,submitted'],
        ];
    }

    public function attributes(): array
    {
        return [
            'site_id' => 'site',
            'client_name' => 'nom de la structure',
            'survey_date' => 'date de l\'enquête',
            'amabilite_ecoute' => 'aimabilité et écoute',
            'disponibilite_spontaneite' => 'disponibilité',
            'rapidite_traitement' => 'rapidité',
            'respect_delais' => 'respect des délais',
            'conformite_produits' => 'conformité',
            'traitement_reclamations' => 'traitement des réclamations',
        ];
    }
}
