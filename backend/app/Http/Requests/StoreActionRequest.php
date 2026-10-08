<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Action::class);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'site_id' => 'required|exists:sites,id',
            'process_id' => 'required|exists:processes,id',
            'type' => 'required|in:corrective,preventive,improvement,emergency,curative',
            'description' => 'required|string|min:10|max:2000',
            'responsible_id' => 'required|exists:users,id',
            'deadline' => 'required|date|after:today',
            'priority' => 'nullable|in:low,medium,high,critical',
            'plan_action_id' => 'nullable|exists:plan_actions,id',
            'source' => 'nullable|in:audit,complaint,non_conformity,suggestion,management_review',
            'source_type' => 'nullable|string|max:50',
            'source_id' => 'nullable|integer',
            'estimated_cost' => 'nullable|numeric|min:0',
            'required_resources' => 'nullable|string|max:5000',
            'actionables' => 'nullable|array',
            'actionables.*.type' => 'required|string',
            'actionables.*.id' => 'required|integer',
        ];
    }
}
