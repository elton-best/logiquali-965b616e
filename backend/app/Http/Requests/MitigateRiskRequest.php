<?php

namespace App\Http\Requests;

use App\Models\Risk;
use Illuminate\Foundation\Http\FormRequest;

class MitigateRiskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $risk = Risk::find($this->route('id'));
        return $risk && $this->user()->can('treat', $risk);
    }

    public function rules(): array
    {
        return [
            'residual_probability' => 'required|integer|min:1|max:4',
            'residual_gravity' => 'required|integer|min:1|max:4',
            'mitigation_measures' => 'required|string|min:10|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'residual_probability.required' => 'La probabilité résiduelle est requise',
            'residual_probability.integer' => 'La probabilité doit être un nombre entier',
            'residual_probability.min' => 'La probabilité doit être au minimum 1',
            'residual_probability.max' => 'La probabilité doit être au maximum 4',
            'residual_gravity.required' => 'La gravité résiduelle est requise',
            'residual_gravity.integer' => 'La gravité doit être un nombre entier',
            'residual_gravity.min' => 'La gravité doit être au minimum 1',
            'residual_gravity.max' => 'La gravité doit être au maximum 4',
            'mitigation_measures.required' => 'Les mesures de mitigation sont requises',
            'mitigation_measures.min' => 'Les mesures doivent contenir au moins 10 caractères',
        ];
    }
}
