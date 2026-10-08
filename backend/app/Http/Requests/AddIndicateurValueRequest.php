<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddIndicateurValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        $indicateur = $this->route('id') ? \App\Models\Indicateur::find($this->route('id')) : null;
        return $indicateur && $this->user()->can('addValue', $indicateur);
    }

    public function rules(): array
    {
        return [
            'value' => 'required|numeric',
            'date' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
