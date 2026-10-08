<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Audit::class);
    }

    public function rules(): array
    {
        return [
            'audit_program_id' => 'nullable|exists:audit_programs,id',
            'site_id' => 'required|exists:sites,id',
            'type' => 'required|in:internal,external,certification,system,process,product,supplier,thematic,surveillance',
            'title' => 'required|string|max:255',
            'planned_date' => 'required|date|after_or_equal:today',
            'quarter' => 'nullable|integer|between:1,4',
            'frequency' => 'nullable|in:annual,biannual,quarterly,monthly,ad_hoc',
            'lead_auditor_id' => 'required|exists:users,id',
            'assigned_to' => 'required_if:type,internal|exists:users,id',
            'team_members' => 'nullable|array',
            'team_members.*' => 'exists:users,id',
            'auditor_ids' => 'nullable|array',
            'auditor_ids.*' => 'exists:users,id',
            'auditee_ids' => 'nullable|array',
            'auditee_ids.*' => 'exists:users,id',
            'scope' => 'nullable|string|max:2000',
            'objectives' => 'required_if:type,internal|string|max:2000',
            'reference_documents' => 'required_if:type,internal|string|max:3000',
            'risk_based_criteria' => 'nullable|string|max:1000',
            'm9_d2_traceability' => 'nullable|array',
            'm9_d5_traceability' => 'nullable|array',
            'axes' => 'nullable|array',
            'axes.*' => 'string|in:Q,HS,E',
            'processes' => 'nullable|array',
            'processes.*' => 'exists:processes,id',
            'process_ids' => 'nullable|array',
            'process_ids.*' => 'exists:processes,id',
        ];
    }
}
