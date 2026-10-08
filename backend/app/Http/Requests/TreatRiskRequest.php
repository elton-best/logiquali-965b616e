<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TreatRiskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $risk = $this->route('id') ? \App\Models\Risk::find($this->route('id')) : null;
        return $risk && $this->user()->can('treat', $risk);
    }

    public function rules(): array
    {
        return [
            'treatment_strategy' => 'required|in:avoid,reduce,transfer,accept',
            'treatment_plan' => 'nullable|string|max:2000',
            'residual_probability' => 'nullable|integer|min:1|max:4',
            'residual_severity' => 'nullable|integer|min:1|max:4',
            'actions' => 'nullable|array',
            'actions.*.description' => 'required|string',
            'actions.*.responsible_id' => 'required|exists:users,id',
            'actions.*.deadline' => 'required|date|after:today',
        ];
    }
}
