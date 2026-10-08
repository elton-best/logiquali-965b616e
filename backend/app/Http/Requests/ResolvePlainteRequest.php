<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolvePlainteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $plainte = $this->route('plainte') ?? \App\Models\Plainte::find($this->route('id'));
        return $this->user()->can('resolve', $plainte);
    }

    public function rules(): array
    {
        return [
            'corrective_actions' => 'required|string',
            'preventive_actions' => 'nullable|string',
            'cost_impact' => 'nullable|numeric|min:0',
            'notification_sent' => 'boolean',
        ];
    }
}
