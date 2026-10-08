<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicableRequirementResource;
use App\Models\ApplicableRequirement;
use Illuminate\Http\Request;

class ApplicableRequirementController extends Controller
{
    public function index()
    {
        $requirements = ApplicableRequirement::with(['site', 'responsible'])->paginate(20);
        return ApplicableRequirementResource::collection($requirements);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'title' => 'required|string|max:255',
            'type' => 'required|in:normative,legal,regulatory,contractual',
            'source' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'compliance_status' => 'required|in:compliant,partial,non_compliant,not_applicable',
            'actions' => 'nullable|string',
            'responsible_id' => 'nullable|exists:users,id',
            'deadline' => 'nullable|date',
        ]);

        $requirement = ApplicableRequirement::create($validated);
        return new ApplicableRequirementResource($requirement->load(['site', 'responsible']));
    }

    public function show(ApplicableRequirement $applicableRequirement)
    {
        return new ApplicableRequirementResource($applicableRequirement->load(['site', 'responsible']));
    }

    public function update(Request $request, ApplicableRequirement $applicableRequirement)
    {
        $validated = $request->validate([
            'site_id' => 'sometimes|exists:sites,id',
            'title' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:normative,legal,regulatory,contractual',
            'source' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'compliance_status' => 'sometimes|in:compliant,partial,non_compliant,not_applicable',
            'actions' => 'nullable|string',
            'responsible_id' => 'nullable|exists:users,id',
            'deadline' => 'nullable|date',
        ]);

        $applicableRequirement->update($validated);
        return new ApplicableRequirementResource($applicableRequirement->load(['site', 'responsible']));
    }

    public function destroy(ApplicableRequirement $applicableRequirement)
    {
        $applicableRequirement->delete();
        return response()->json(null, 204);
    }
}
