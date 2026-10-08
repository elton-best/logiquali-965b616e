<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmergencyProcedure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmergencyProcedureController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(EmergencyProcedure::query()
            ->with('responsible:id,name,email')
            ->where('enterprise_id', $request->user()->enterprise_id)
            ->when($request->filled('site_id'), fn ($query) => $query->where('site_id', $request->integer('site_id')))
            ->latest('id')->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['enterprise_id'] = $request->user()->enterprise_id;
        $data['site_id'] = $data['site_id'] ?? $request->user()->site_id;
        $procedure = EmergencyProcedure::create($data);
        return response()->json(['data' => $procedure->load('responsible:id,name,email')], 201);
    }

    public function show(Request $request, EmergencyProcedure $emergencyProcedure): JsonResponse
    {
        $this->assertScope($request, $emergencyProcedure);
        return response()->json(['data' => $emergencyProcedure->load('responsible:id,name,email')]);
    }

    public function update(Request $request, EmergencyProcedure $emergencyProcedure): JsonResponse
    {
        $this->assertScope($request, $emergencyProcedure);
        $emergencyProcedure->update($this->validated($request, true));
        return response()->json(['data' => $emergencyProcedure->fresh()->load('responsible:id,name,email')]);
    }

    public function destroy(Request $request, EmergencyProcedure $emergencyProcedure): JsonResponse
    {
        $this->assertScope($request, $emergencyProcedure);
        $emergencyProcedure->delete();
        return response()->json(['message' => 'Situation d’urgence supprimée.']);
    }

    private function validated(Request $request, bool $update = false): array
    {
        $required = $update ? 'sometimes' : 'required';
        return $request->validate([
            'site_id' => ['nullable', 'integer', 'exists:sites,id'],
            'emergency_type' => [$required, 'string', 'max:100'],
            'title' => [$required, 'string', 'max:255'],
            'preparation_measures' => ['nullable', 'string', 'max:10000'],
            'responsible_id' => ['nullable', 'integer', 'exists:users,id'],
            'deadline' => ['nullable', 'date'],
            'procedure_steps' => ['nullable', 'array'],
            'emergency_contacts' => ['nullable', 'array'],
            'required_equipment' => ['nullable', 'array'],
            'training_required' => ['nullable', 'boolean'],
            'trained_users' => ['nullable', 'array'],
            'last_drill_date' => ['nullable', 'date'],
            'next_drill_date' => ['nullable', 'date'],
            'document_path' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function assertScope(Request $request, EmergencyProcedure $procedure): void
    {
        abort_unless($request->user()->isSuperAdmin() || (int) $procedure->enterprise_id === (int) $request->user()->enterprise_id, 403, 'Accès refusé.');
    }
}
