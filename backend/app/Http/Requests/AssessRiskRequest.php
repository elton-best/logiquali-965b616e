<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssessRiskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $risk = $this->route('id') ? \App\Models\Risk::find($this->route('id')) : null;
        return $risk && $this->user()->can('assess', $risk);
    }

    public function rules(): array
    {
        return [
            'probability' => 'required|integer|min:1|max:4',
            'severity' => 'required|integer|min:1|max:4',
            'detectability' => 'nullable|integer|min:1|max:4',
            'assessment_notes' => 'nullable|string|max:1000',
        ];
    }
}
