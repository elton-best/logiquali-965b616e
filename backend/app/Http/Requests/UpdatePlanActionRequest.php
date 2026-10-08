<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $planAction = $this->route('id') ? \App\Models\PlanAction::find($this->route('id')) : null;
        return $planAction && $this->user()->can('update', $planAction);
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
            'responsible_id' => 'nullable|exists:users,id',
            'deadline' => 'nullable|date|after:today',
            'axes' => 'nullable|array',
            'axes.*' => 'string|in:Q,HS,E',
        ];
    }
}
