<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        $audit = $this->route('id') ? \App\Models\Audit::find($this->route('id')) : null;
        return $audit && $this->user()->can('update', $audit);
    }

    public function rules(): array
    {
        return [
            'type' => 'sometimes|in:internal,external,certification,system,process,product,supplier,thematic,surveillance',
            'title' => 'sometimes|string|max:255',
            'planned_date' => 'sometimes|date|after_or_equal:today',
            'lead_auditor_id' => 'sometimes|exists:users,id',
            'assigned_to' => 'required_if:type,internal|exists:users,id',
            'team_members' => 'nullable|array',
            'team_members.*' => 'exists:users,id',
            'scope' => 'nullable|string|max:2000',
            'objectives' => 'required_if:type,internal|string|max:2000',
            'reference_documents' => 'required_if:type,internal|string|max:3000',
            'report_source' => 'sometimes|in:auto,external',
            'report_version' => 'sometimes|integer|min:1|max:999',
            'm9_d2_traceability' => 'nullable|array',
            'm9_d5_traceability' => 'nullable|array',
        ];
    }
}
