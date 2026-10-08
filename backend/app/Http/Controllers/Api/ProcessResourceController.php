<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProcessResourceResource;
use App\Models\ProcessResource;
use Illuminate\Http\Request;

class ProcessResourceController extends Controller
{
    public function index()
    {
        $resources = ProcessResource::with('process')->paginate(20);
        return ProcessResourceResource::collection($resources);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'process_id' => 'required|exists:processes,id',
            'type' => 'required|in:human,technological,documentary,material',
            'description' => 'required|string',
            'quantity' => 'nullable|string',
            'is_available' => 'boolean',
        ]);

        $resource = ProcessResource::create($validated);
        return new ProcessResourceResource($resource->load('process'));
    }

    public function show(ProcessResource $processResource)
    {
        return new ProcessResourceResource($processResource->load('process'));
    }

    public function update(Request $request, ProcessResource $processResource)
    {
        $validated = $request->validate([
            'process_id' => 'sometimes|exists:processes,id',
            'type' => 'sometimes|in:human,technological,documentary,material',
            'description' => 'sometimes|string',
            'quantity' => 'nullable|string',
            'is_available' => 'boolean',
        ]);

        $processResource->update($validated);
        return new ProcessResourceResource($processResource->load('process'));
    }

    public function destroy(ProcessResource $processResource)
    {
        $processResource->delete();
        return response()->json(null, 204);
    }
}
