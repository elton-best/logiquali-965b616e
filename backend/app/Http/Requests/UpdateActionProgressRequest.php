<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActionProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        $id = $this->route('id') ?? $this->route('action') ?? null;
        \Illuminate\Support\Facades\Log::debug('UpdateActionProgressRequest authorize', [
            'route_params' => $this->route() ? $this->route()->parameters() : null,
            'resolved_id' => $id,
            'user_id' => $this->user()?->id ?? null,
        ]);

        $action = $id ? \App\Models\Action::find($id) : null;
        return $action && $this->user()->can('updateProgress', $action);
    }

    public function rules(): array
    {
        return [
            'progress' => 'required|integer|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
