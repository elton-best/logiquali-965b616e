<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProcessInteractionResource;
use App\Models\ProcessInteraction;
use Illuminate\Http\Request;

class ProcessInteractionController extends Controller
{
    public function index()
    {
        $interactions = ProcessInteraction::with(['supplierProcess', 'selfProcess', 'clientProcess'])->paginate(20);
        return ProcessInteractionResource::collection($interactions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_process_id' => 'required|exists:processes,id',
            'self_process_id' => 'required|exists:processes,id',
            'client_process_id' => 'required|exists:processes,id',
            'description' => 'nullable|string',
        ]);

        $interaction = ProcessInteraction::create($validated);
        return new ProcessInteractionResource($interaction->load(['supplierProcess', 'selfProcess', 'clientProcess']));
    }

    public function show(ProcessInteraction $processInteraction)
    {
        return new ProcessInteractionResource($processInteraction->load(['supplierProcess', 'selfProcess', 'clientProcess']));
    }

    public function update(Request $request, ProcessInteraction $processInteraction)
    {
        $validated = $request->validate([
            'supplier_process_id' => 'sometimes|exists:processes,id',
            'self_process_id' => 'sometimes|exists:processes,id',
            'client_process_id' => 'sometimes|exists:processes,id',
            'description' => 'nullable|string',
        ]);

        $processInteraction->update($validated);
        return new ProcessInteractionResource($processInteraction->load(['supplierProcess', 'selfProcess', 'clientProcess']));
    }

    public function destroy(ProcessInteraction $processInteraction)
    {
        $processInteraction->delete();
        return response()->json(null, 204);
    }
}
