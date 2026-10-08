<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlainteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Plainte::class);
    }

    public function rules(): array
    {
        return [
            'site_id' => 'required|exists:sites,id',
            'plaignant_name' => 'required_if:anonymous,false|string|max:255',
            'plaignant_email' => 'nullable|email|max:255',
            'plaignant_phone' => 'nullable|string|max:50',
            'plaignant_company' => 'nullable|string|max:255',
            'stakeholder_type' => 'required|in:employee,client,supplier,contractor,neighbor,authority,other',
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:20',
            'category' => 'required|in:discrimination,harassment,safety,ethics,environment,working_conditions,management,other',
            'severity' => 'required|in:low,medium,high,critical',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'received_date' => 'nullable|date|before_or_equal:today',
            'confidential' => 'boolean',
            'anonymous' => 'boolean',
            'axes' => 'nullable|array',
            'axes.*' => 'string|in:Q,HS,E',
        ];
    }

    public function messages(): array
    {
        return [
            'plaignant_name.required_if' => 'Le nom du plaignant est requis pour les plaintes non-anonymes',
            'description.min' => 'La description doit contenir au moins 20 caractères',
        ];
    }
}
