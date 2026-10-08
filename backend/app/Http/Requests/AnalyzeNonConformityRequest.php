<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnalyzeNonConformityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $nc = $this->route('id') ? \App\Models\NonConformity::find($this->route('id')) : null;
        return $nc && $this->user()->can('analyze', $nc);
    }

    public function rules(): array
    {
        return [
            'method' => 'required|in:5why,ishikawa,fishbone',
            'data' => 'required|array',
            'data.why1' => 'required_if:method,5why|string|max:500',
            'data.why2' => 'nullable|string|max:500',
            'data.why3' => 'nullable|string|max:500',
            'data.why4' => 'nullable|string|max:500',
            'data.why5' => 'nullable|string|max:500',
            'data.materials' => 'required_if:method,ishikawa|array',
            'data.methods' => 'required_if:method,ishikawa|array',
            'data.machines' => 'required_if:method,ishikawa|array',
            'data.manpower' => 'required_if:method,ishikawa|array',
            'data.environment' => 'required_if:method,ishikawa|array',
            'root_cause' => 'nullable|string|max:1000',
            'impacts' => 'nullable|array',
            'impacts.*.type' => 'required|in:quality,safety,environment,financial,reputation',
            'impacts.*.description' => 'required|string',
            'corrective_action' => 'nullable|string|max:2000',
            'preventive_action' => 'nullable|string|max:2000',
        ];
    }
}
