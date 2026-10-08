<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RespondReclamationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reclamation = $this->route('id') ? \App\Models\Reclamation::find($this->route('id')) : null;
        return $reclamation && $this->user()->can('respond', $reclamation);
    }

    public function rules(): array
    {
        return [
            'response' => 'required|string|min:20|max:5000',
            'send_email' => 'nullable|boolean',
        ];
    }
}
