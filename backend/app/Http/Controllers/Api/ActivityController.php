<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index()
    {
        $activities = Activity::with(['process', 'responsible'])->paginate(20);
        return ActivityResource::collection($activities);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'process_id' => 'required|exists:processes,id',
            'title' => 'required|string|max:255',
            'input' => 'nullable|string',
            'output' => 'nullable|string',
            'order' => 'required|integer',
            'responsible_id' => 'nullable|exists:users,id',
        ]);

        $activity = Activity::create($validated);
        return new ActivityResource($activity->load(['process', 'responsible']));
    }

    public function show(Activity $activity)
    {
        return new ActivityResource($activity->load(['process', 'responsible']));
    }

    public function update(Request $request, Activity $activity)
    {
        $validated = $request->validate([
            'process_id' => 'sometimes|exists:processes,id',
            'title' => 'sometimes|string|max:255',
            'input' => 'nullable|string',
            'output' => 'nullable|string',
            'order' => 'sometimes|integer',
            'responsible_id' => 'nullable|exists:users,id',
        ]);

        $activity->update($validated);
        return new ActivityResource($activity->load(['process', 'responsible']));
    }

    public function destroy(Activity $activity)
    {
        $activity->delete();
        return response()->json(null, 204);
    }
}

