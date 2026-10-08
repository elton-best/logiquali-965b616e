<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIndicateurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Indicateur::class);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:indicateurs,code',
            'type' => 'required|in:performance,quality,safety,environment,compliance,efficiency',
            'formula' => 'nullable|string|max:500',
            'unit' => 'nullable|string|max:50',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly,yearly',
            'target_value' => 'nullable|numeric',
            'min_threshold' => 'nullable|numeric',
            'max_threshold' => 'nullable|numeric|gt:min_threshold',
            'responsible_id' => 'nullable|exists:users,id',
            'axes' => 'nullable|array',
            'axes.*' => 'string|in:Q,HS,E',
        ];
    }
}
