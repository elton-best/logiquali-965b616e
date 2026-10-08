<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvestigatePlainteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $plainte = $this->route('plainte') ?? \App\Models\Plainte::find($this->route('id'));
        return $this->user()->can('investigate', $plainte);
    }

    public function rules(): array
    {
        return [
            'analysis' => 'required|string',
            'corrective_actions' => 'nullable|string',
            'preventive_actions' => 'nullable|string',
            'witnesses' => 'nullable|array',
            'witnesses.*.name' => 'required|string',
            'witnesses.*.statement' => 'nullable|string',
            'evidence' => 'nullable|array',
            'evidence.*' => 'file|max:10240', // 10MB max
        ];
    }
}
