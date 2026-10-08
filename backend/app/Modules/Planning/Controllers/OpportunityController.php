<?php

namespace App\Modules\Planning\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\OpportunityResource;
use App\Models\Opportunity;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function index(Request $request)
    {
        $query = Opportunity::with(['site', 'process', 'responsible']);

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->integer('site_id'));
        }

        if ($request->filled('process_id')) {
            $query->where('process_id', $request->integer('process_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        $opportunities = $query
            ->orderByDesc('created_at')
            ->paginate(20);

        return OpportunityResource::collection($opportunities);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'nullable|exists:sites,id',
            'process_id' => 'nullable|exists:processes,id',
            'description' => 'required|string',
            'exploitation_action' => 'nullable|string',
            'responsible_id' => 'nullable|exists:users,id',
            'deadline' => 'nullable|date',
            'status' => 'required|in:identified,evaluated,exploited,monitored',
        ]);

        $opportunity = Opportunity::create($validated);
        return new OpportunityResource($opportunity->load(['site', 'process', 'responsible']));
    }

    public function show(Opportunity $opportunity)
    {
        return new OpportunityResource($opportunity->load(['site', 'process', 'responsible']));
    }

    public function update(Request $request, Opportunity $opportunity)
    {
        $validated = $request->validate([
            'site_id' => 'nullable|exists:sites,id',
            'process_id' => 'sometimes|exists:processes,id',
            'description' => 'sometimes|string',
            'exploitation_action' => 'nullable|string',
            'responsible_id' => 'nullable|exists:users,id',
            'deadline' => 'nullable|date',
            'status' => 'sometimes|in:identified,evaluated,exploited,monitored',
        ]);

        $opportunity->update($validated);
        return new OpportunityResource($opportunity->load(['site', 'process', 'responsible']));
    }

    public function destroy(Opportunity $opportunity)
    {
        $opportunity->delete();
        return response()->json(null, 204);
    }
}
