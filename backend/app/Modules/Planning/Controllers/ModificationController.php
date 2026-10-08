<?php

namespace App\Modules\Planning\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\ModificationResource;
use App\Models\Modification;
use Illuminate\Http\Request;

class ModificationController extends Controller
{
    public function index()
    {
        $modifications = Modification::with(['site', 'responsible', 'validator'])->paginate(20);
        return ModificationResource::collection($modifications);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'number' => 'required|string|max:255',
            'date' => 'required|date',
            'object' => 'required|string|max:255',
            'description' => 'required|string',
            'objectives' => 'nullable|string',
            'consequences' => 'nullable|string',
            'required_resources' => 'nullable|string',
            'responsible_id' => 'nullable|exists:users,id',
            'validated_by' => 'nullable|exists:users,id',
            'validated_at' => 'nullable|date',
            'status' => 'required|in:pending,approved,implemented,rejected',
        ]);

        $modification = Modification::create($validated);
        return new ModificationResource($modification->load(['site', 'responsible', 'validator']));
    }

    public function show(Modification $modification)
    {
        return new ModificationResource($modification->load(['site', 'responsible', 'validator']));
    }

    public function update(Request $request, Modification $modification)
    {
        $validated = $request->validate([
            'site_id' => 'sometimes|exists:sites,id',
            'number' => 'sometimes|string|max:255',
            'date' => 'sometimes|date',
            'object' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'objectives' => 'nullable|string',
            'consequences' => 'nullable|string',
            'required_resources' => 'nullable|string',
            'responsible_id' => 'nullable|exists:users,id',
            'validated_by' => 'nullable|exists:users,id',
            'validated_at' => 'nullable|date',
            'status' => 'sometimes|in:pending,approved,implemented,rejected',
        ]);

        $modification->update($validated);
        return new ModificationResource($modification->load(['site', 'responsible', 'validator']));
    }

    public function destroy(Modification $modification)
    {
        $modification->delete();
        return response()->json(null, 204);
    }
}
