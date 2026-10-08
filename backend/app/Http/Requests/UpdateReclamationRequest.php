<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReclamationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $routeId = $this->route('reclamation') ?? $this->route('id');
        $reclamation = $routeId ? \App\Models\Reclamation::find($routeId) : null;
        return $reclamation && $this->user()->can('update', $reclamation);
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'nullable|exists:stakeholders,id',
            'customer_name' => 'sometimes|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'client_name' => 'nullable|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'client_phone' => 'nullable|string|max:30',
            'client_company' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'type' => 'sometimes|in:product,service,delivery,quality,safety,other',
            'description' => 'sometimes|string|min:10|max:5000',
            'received_at' => 'nullable|date|before_or_equal:today',
            'received_date' => 'nullable|date|before_or_equal:today',
            'due_date' => 'nullable|date',
            'wants_mail' => 'nullable|boolean',
            'analysis' => 'nullable|string',
            'recommandations' => 'nullable|string',
            'immediate_response' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'nullable|string|max:50',
            'axes' => 'nullable|array',
            'axes.*' => 'string|in:Q,HS,E',
            'actions' => 'nullable|array',
            'actions.*.type' => 'required|in:corrective,preventive,improvement',
            'actions.*.description' => 'required|string',
            'actions.*.responsible_id' => 'required|exists:users,id',
            'actions.*.deadline' => 'required|date|after:today',
            'actions.*.process_id' => 'nullable|exists:processes,id',
            'actions.*.title' => 'nullable|string|max:255',
        ];
    }
}
