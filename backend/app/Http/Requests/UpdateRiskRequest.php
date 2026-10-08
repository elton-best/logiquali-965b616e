<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRiskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $risk = $this->route('id') ? \App\Models\Risk::find($this->route('id')) : null;
        return $risk && $this->user()->can('update', $risk);
    }

    public function rules(): array
    {
        return [
            'process_id' => 'nullable|exists:processes,id',
            'type' => 'sometimes|in:risk,opportunity',
            'category' => 'sometimes|in:strategic,operational,financial,compliance,safety,environmental,reputation,it,legal,other',
            'description' => 'sometimes|string|min:10|max:2000',
            'responsible_id' => 'nullable|exists:users,id',
            'axes' => 'nullable|array',
            'axes.*' => 'string|in:Q,HS,E',
        ];
    }
}
