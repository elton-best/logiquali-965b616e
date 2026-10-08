<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRiskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Risk::class);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'site_id' => 'required|exists:sites,id',
            'process_id' => 'nullable|exists:processes,id',
            'type' => 'required|in:risk,opportunity',
            'category' => 'required|in:strategic,operational,financial,compliance,safety,environmental,reputation,it,legal,other',
            'description' => 'required|string|min:10|max:2000',
            'probability' => 'nullable|integer|min:1|max:4',
            'gravity' => 'nullable|integer|min:1|max:4',
            'responsible_id' => 'nullable|exists:users,id',
            'axes' => 'nullable|array',
            'axes.*' => 'string|in:Q,HS,E',
        ];
    }
}
