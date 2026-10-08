<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlainteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $plainte = $this->route('plainte') ?? \App\Models\Plainte::find($this->route('id'));
        return $this->user()->can('update', $plainte);
    }

    public function rules(): array
    {
        return [
            'plaignant_name' => 'sometimes|string|max:255',
            'plaignant_email' => 'nullable|email|max:255',
            'plaignant_phone' => 'nullable|string|max:50',
            'plaignant_company' => 'nullable|string|max:255',
            'stakeholder_type' => 'sometimes|in:employee,client,supplier,contractor,neighbor,authority,other',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|min:20',
            'category' => 'sometimes|in:discrimination,harassment,safety,ethics,environment,working_conditions,management,other',
            'severity' => 'sometimes|in:low,medium,high,critical',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'due_date' => 'nullable|date',
            'confidential' => 'boolean',
            'axes' => 'nullable|array',
            'axes.*' => 'string|in:Q,HS,E',
        ];
    }
}
