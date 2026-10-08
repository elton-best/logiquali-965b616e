<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $action = $this->route('id') ? \App\Models\Action::find($this->route('id')) : null;
        return $action && $this->user()->can('update', $action);
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'site_id' => 'sometimes|exists:sites,id',
            'process_id' => 'sometimes|exists:processes,id',
            'type' => 'sometimes|in:corrective,preventive,improvement,emergency,curative',
            'description' => 'sometimes|string|min:10|max:2000',
            'responsible_id' => 'sometimes|exists:users,id',
            'deadline' => 'sometimes|date|after:today',
            'priority' => 'nullable|in:low,medium,high,critical',
            'plan_action_id' => 'nullable|exists:plan_actions,id',
            'source' => 'nullable|in:audit,complaint,non_conformity,suggestion,management_review',
            'source_type' => 'nullable|string|max:50',
            'source_id' => 'nullable|integer',
            'estimated_cost' => 'nullable|numeric|min:0',
            'actual_cost' => 'nullable|numeric|min:0',
            'required_resources' => 'nullable|string|max:5000',
            'status' => 'sometimes|in:planned,in_progress,completed,verified,cancelled',
        ];
    }
}
