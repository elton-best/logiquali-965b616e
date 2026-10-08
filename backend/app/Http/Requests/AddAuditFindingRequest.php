<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddAuditFindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $audit = $this->route('id') ? \App\Models\Audit::find($this->route('id')) : null;
        return $audit && $this->user()->can('addFinding', $audit);
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:nc_major,nc_minor,observation,opportunity',
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:10|max:2000',
            'evidence' => 'nullable|string|max:1000',
            'clause_iso' => 'nullable|string|max:50',
            'process_id' => 'nullable|exists:processes,id',
            'severity' => 'required|in:low,medium,high,critical',
            'priority' => 'required|integer|between:1,5',
        ];
    }
}
