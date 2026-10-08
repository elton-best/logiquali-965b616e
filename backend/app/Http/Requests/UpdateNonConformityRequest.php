<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNonConformityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $routeId = $this->route('non_conformity') ?? $this->route('id');
        $nc = $routeId ? \App\Models\NonConformity::find($routeId) : null;
        return $nc && $this->user()->can('update', $nc);
    }

    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'finding_type' => 'nullable|in:non_conformity,gap',
            'process_id' => 'nullable|exists:processes,id',
            'type' => 'nullable|in:normative,legal,regulatory,contractual,procedural',
            'source' => 'nullable|in:audit,complaint,internal,external,risk,process',
            'detection_source' => 'nullable|in:internal_audit,external_audit,customer_complaint,internal_control,management_review,other',
            'severity' => 'nullable|in:minor,major,critical',
            'priority' => 'nullable|in:low,medium,high,critical',
            'description' => 'nullable|string|min:10|max:5000',
            'requirement_reference' => 'nullable|string|max:255',
            'detected_at' => 'nullable|date|before_or_equal:today',
            'root_cause_analysis' => 'nullable',
            'corrective_action' => 'nullable|string|max:5000',
            'preventive_action' => 'nullable|string|max:5000',
            'responsible_id' => 'nullable|exists:users,id',
            'investigator_user_ids' => 'nullable|array',
            'investigator_user_ids.*' => 'integer|exists:users,id|distinct',
            'deadline' => 'nullable|date|after:today',
            'result_summary' => 'nullable|string|max:5000',
            'rq_signature_date' => 'nullable|date',
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
