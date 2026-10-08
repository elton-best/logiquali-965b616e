<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateNonConformityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $nc = $this->route('id') ? \App\Models\NonConformity::find($this->route('id')) : null;
        return $nc && $this->user()->can('validate', $nc);
    }

    public function rules(): array
    {
        return [
            'responsible_id' => 'required|exists:users,id',
            'deadline' => 'required|date|after:today',
            'actions' => 'nullable|array',
            'actions.*.type' => 'required|in:corrective,preventive,improvement',
            'actions.*.description' => 'required|string',
            'actions.*.responsible_id' => 'required|exists:users,id',
            'actions.*.deadline' => 'required|date|after:today',
        ];
    }
}
