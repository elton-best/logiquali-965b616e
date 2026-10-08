<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyNonConformityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $nc = $this->route('id') ? \App\Models\NonConformity::find($this->route('id')) : null;
        return $nc && $this->user()->can('verify', $nc);
    }

    public function rules(): array
    {
        return [
            'is_effective' => 'required|boolean',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
