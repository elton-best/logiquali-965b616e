<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNonConformityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\NonConformity::class);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'finding_type' => 'nullable|in:non_conformity,gap',
            'site_id' => 'required|exists:sites,id',
            'process_id' => 'nullable|exists:processes,id',
            'audit_id' => 'nullable|exists:audits,id',
            'risk_id' => 'nullable|exists:risks,id',
            'type' => 'required|in:normative,legal,regulatory,contractual,procedural',
            'source' => 'nullable|in:audit,complaint,internal,external,risk,process',
            'detection_source' => 'nullable|in:internal_audit,external_audit,customer_complaint,internal_control,management_review,other',
            'severity' => 'required|in:minor,major,critical',
            'priority' => 'nullable|in:low,medium,high,critical',
            'description' => 'required|string|min:10|max:5000',
            'requirement_reference' => 'nullable|string|max:255',
            'detected_by' => 'nullable|exists:users,id',
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
            'documents' => 'nullable|array',
            'documents.*' => 'exists:documents,id',
            'actions' => 'nullable|array',
            'actions.*.type' => 'required|in:corrective,preventive,improvement',
            'actions.*.description' => 'required|string',
            'actions.*.responsible_id' => 'required|exists:users,id',
            'actions.*.deadline' => 'required|date|after:today',
            'actions.*.process_id' => 'nullable|exists:processes,id',
            'actions.*.title' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'site_id.required' => 'Le site est obligatoire',
            'type.required' => 'Le type de non-conformité est obligatoire',
            'severity.required' => 'La gravité est obligatoire',
            'description.required' => 'La description est obligatoire',
            'description.min' => 'La description doit contenir au moins 10 caractères',
            'deadline.after' => 'La date limite doit être dans le futur',
        ];
    }
}
