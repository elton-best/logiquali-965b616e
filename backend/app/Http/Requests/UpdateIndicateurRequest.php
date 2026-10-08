<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIndicateurRequest extends FormRequest
{
    public function authorize(): bool
    {
        $indicateur = $this->route('id') ? \App\Models\Indicateur::find($this->route('id')) : null;
        return $indicateur && $this->user()->can('update', $indicateur);
    }

    public function rules(): array
    {
        $indicateurId = $this->route('id');
        
        return [
            'name' => 'sometimes|string|max:255',
            'code' => "sometimes|string|max:50|unique:indicateurs,code,{$indicateurId}",
            'type' => 'sometimes|in:performance,quality,safety,environment,compliance,efficiency',
            'formula' => 'nullable|string|max:500',
            'unit' => 'nullable|string|max:50',
            'frequency' => 'sometimes|in:daily,weekly,monthly,quarterly,yearly',
            'target_value' => 'nullable|numeric',
            'min_threshold' => 'nullable|numeric',
            'max_threshold' => 'nullable|numeric|gt:min_threshold',
            'responsible_id' => 'nullable|exists:users,id',
            'axes' => 'nullable|array',
            'axes.*' => 'string|in:Q,HS,E',
        ];
    }
}
