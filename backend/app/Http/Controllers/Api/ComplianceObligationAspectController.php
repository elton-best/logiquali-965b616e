<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ComplianceObligationAspect;
use Illuminate\Http\Request;

class ComplianceObligationAspectController extends Controller
{
    public function index(Request $request)
    {
        $query = ComplianceObligationAspect::query()->with(['norms:id,code,name']);

        if ($request->filled('site_id')) {
            $query->where('site_id', (int) $request->site_id);
        }

        if ($request->filled('q')) {
            $term = '%' . trim((string) $request->q) . '%';
            $query->where('name', 'ilike', $term);
        }

        $aspects = $query->orderBy('name')->get();

        return response()->json([
            'data' => $aspects->map(fn (ComplianceObligationAspect $aspect) => [
                'id' => $aspect->id,
                'site_id' => $aspect->site_id,
                'name' => $aspect->name,
                'description' => $aspect->description,
                'norms' => $aspect->norms->map(fn ($norm) => [
                    'id' => $norm->id,
                    'code' => $norm->code,
                    'name' => $norm->name,
                ])->values(),
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'norm_ids' => 'nullable|array',
            'norm_ids.*' => 'exists:norms,id',
        ]);

        $aspect = ComplianceObligationAspect::create([
            'site_id' => (int) $validated['site_id'],
            'name' => trim((string) $validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        $aspect->norms()->sync(array_values(array_unique($validated['norm_ids'] ?? [])));

        return response()->json([
            'data' => [
                'id' => $aspect->id,
                'site_id' => $aspect->site_id,
                'name' => $aspect->name,
                'description' => $aspect->description,
            ],
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $aspect = ComplianceObligationAspect::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'norm_ids' => 'sometimes|array',
            'norm_ids.*' => 'exists:norms,id',
        ]);

        $aspect->update([
            'name' => array_key_exists('name', $validated) ? trim((string) $validated['name']) : $aspect->name,
            'description' => array_key_exists('description', $validated) ? $validated['description'] : $aspect->description,
        ]);

        if (array_key_exists('norm_ids', $validated)) {
            $aspect->norms()->sync(array_values(array_unique($validated['norm_ids'] ?? [])));
        }

        $aspect->load('norms:id,code,name');

        return response()->json([
            'data' => [
                'id' => $aspect->id,
                'site_id' => $aspect->site_id,
                'name' => $aspect->name,
                'description' => $aspect->description,
                'norms' => $aspect->norms->map(fn ($norm) => [
                    'id' => $norm->id,
                    'code' => $norm->code,
                    'name' => $norm->name,
                ])->values(),
            ],
        ]);
    }
}
