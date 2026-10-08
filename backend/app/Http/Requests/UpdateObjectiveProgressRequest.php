<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateObjectiveProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        $objective = $this->route('id') ? \App\Models\Objective::find($this->route('id')) : null;
        return $objective && $this->user()->can('updateProgress', $objective);
    }

    public function rules(): array
    {
        return [
            'current_value' => 'required|numeric',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
