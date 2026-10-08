<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateObjectiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        $objective = $this->route('id') ? \App\Models\Objective::find($this->route('id')) : null;
        return $objective && $this->user()->can('update', $objective);
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
            'type' => 'sometimes|in:strategic,operational,quality,safety,environmental',
            'target_value' => 'sometimes|numeric',
            'unit' => 'nullable|string|max:50',
            'deadline' => 'sometimes|date|after:today',
            'responsible_id' => 'sometimes|exists:users,id',
            'indicateur_id' => 'nullable|exists:indicateurs,id',
            'axes' => 'nullable|array',
            'axes.*' => 'string|in:Q,HS,E',
            'planned_actions' => 'nullable|array',
            'planned_actions.*.id' => 'nullable|integer|exists:actions,id',
            'planned_actions.*.title' => 'required_with:planned_actions|string|max:255',
            'planned_actions.*.description' => 'nullable|string|max:2000',
            'planned_actions.*.responsible_user_id' => 'nullable|integer|exists:users,id',
            'planned_actions.*.start_date' => 'nullable|date',
            'planned_actions.*.due_date' => 'nullable|date',
            'planned_actions.*.status' => 'nullable|string|in:a_faire,en_cours,terminee,draft,planned,completed',
            'planned_actions.*.progress' => 'nullable|integer|min:0|max:100',
        ];
    }
}
