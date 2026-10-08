<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StrategicAxisResource;
use App\Models\StrategicAxis;
use Illuminate\Http\Request;

class StrategicAxisController extends Controller
{
    public function index()
    {
        $axes = StrategicAxis::with(['site', 'objectives'])->paginate(20);
        return StrategicAxisResource::collection($axes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_year' => 'required|integer|min:2020|max:2100',
            'end_year' => 'nullable|integer|min:2020|max:2100',
            'is_active' => 'boolean',
        ]);

        $axis = StrategicAxis::create($validated);
        return new StrategicAxisResource($axis->load('site'));
    }

    public function show(StrategicAxis $strategicAxis)
    {
        return new StrategicAxisResource($strategicAxis->load(['site', 'objectives']));
    }

    public function update(Request $request, StrategicAxis $strategicAxis)
    {
        $validated = $request->validate([
            'site_id' => 'sometimes|exists:sites,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_year' => 'sometimes|integer|min:2020|max:2100',
            'end_year' => 'nullable|integer|min:2020|max:2100',
            'is_active' => 'boolean',
        ]);

        $strategicAxis->update($validated);
        return new StrategicAxisResource($strategicAxis->load('site'));
    }

    public function destroy(StrategicAxis $strategicAxis)
    {
        $strategicAxis->delete();
        return response()->json(null, 204);
    }
}
