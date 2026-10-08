<?php

namespace App\Modules\Hse\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hse\Models\EnvironmentalAspect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnvironmentalAspectController extends Controller
{
    /**
     * Display a listing of environmental aspects for the enterprise.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $query = EnvironmentalAspect::query()
            ->where('enterprise_id', $user->enterprise_id)
            ->with(['process:id,code,title', 'site:id,name', 'responsible:id,name']);

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->query('site_id'));
        }

        if ($request->filled('process_id')) {
            $query->where('process_id', $request->query('process_id'));
        }

        if ($request->filled('mode')) {
            $query->where('mode', $request->query('mode'));
        }

        if ($request->filled('is_significant')) {
            $isSig = filter_var($request->query('is_significant'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_significant', $isSig);
        }

        if ($request->filled('search')) {
            $s = trim($request->query('search'));
            $query->where(function ($q) use ($s) {
                $q->where('activity', 'ilike', "%{$s}%")
                    ->orWhere('aspect', 'ilike', "%{$s}%")
                    ->orWhere('impact', 'ilike', "%{$s}%")
                    ->orWhere('risk', 'ilike', "%{$s}%");
            });
        }

        $aspects = $query->orderBy('process_id')->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'data' => $aspects,
            'meta' => [
                'total' => $aspects->count(),
                'significant_count' => $aspects->where('is_significant', true)->count(),
                'non_significant_count' => $aspects->where('is_significant', false)->count(),
            ],
        ]);
    }

    /**
     * Export Environmental Aspects (AES) to Excel (by enterprise and by site / process).
     */
    public function exportXlsx(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $filters = [
            'site_id' => $request->filled('site_id') ? (int) $request->site_id : ($user->site_id ?: null),
            'process_id' => $request->filled('process_id') ? (int) $request->process_id : null,
            'is_significant' => $request->filled('is_significant') ? filter_var($request->query('is_significant'), FILTER_VALIDATE_BOOLEAN) : null,
        ];

        $exportService = app(\App\Modules\Hse\Services\HseExportService::class);
        $tempFile = $exportService->exportEnvironmentalAspectsXlsx($user->enterprise_id, $filters);

        $siteSuffix = !empty($filters['site_id']) ? "_site_{$filters['site_id']}" : '_tous_sites';
        $filename = 'aes_iso14001' . $siteSuffix . '_' . date('Ymd_His') . '.xlsx';

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Store a newly created environmental aspect.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $validated = $request->validate([
            'site_id' => 'nullable|exists:sites,id',
            'process_id' => 'nullable|exists:processes,id',
            'sub_process' => 'nullable|string|max:255',
            'mode' => 'nullable|in:N,A',
            'aspect_number' => 'nullable|string|max:50',
            'activity' => 'required|string|max:255',
            'aspect' => 'required|string|max:255',
            'impact' => 'required|string|max:255',
            'risk' => 'nullable|string',
            'existing_controls' => 'nullable|string',
            'gravity' => 'nullable|integer|min:1|max:5',
            'frequency' => 'nullable|integer|min:1|max:7',
            'sensitivity' => 'nullable|integer|min:1|max:5',
            'mastery' => 'nullable|integer|min:1|max:5',
            'significance_threshold' => 'nullable|integer',
            'additional_actions' => 'nullable|string',
            'responsible_id' => 'nullable|exists:users,id',
            'responsible_name' => 'nullable|string|max:255',
            'deadline' => 'nullable|date',
            'effectiveness_criterion' => 'nullable|string',
            'efficiency_criterion' => 'nullable|string',
            'observations' => 'nullable|string',
        ]);

        $validated['enterprise_id'] = $user->enterprise_id;
        $validated['created_by'] = $user->id;

        $aspect = EnvironmentalAspect::create($validated);
        $aspect->load(['process:id,code,title', 'site:id,name', 'responsible:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Aspect environnemental enregistré avec succès.',
            'data' => $aspect,
        ], 201);
    }

    /**
     * Display the specified environmental aspect.
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();
        $aspect = EnvironmentalAspect::where('enterprise_id', $user->enterprise_id)
            ->with(['process:id,code,title', 'site:id,name', 'responsible:id,name'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $aspect,
        ]);
    }

    /**
     * Update the specified environmental aspect.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $aspect = EnvironmentalAspect::where('enterprise_id', $user->enterprise_id)->findOrFail($id);

        $validated = $request->validate([
            'site_id' => 'nullable|exists:sites,id',
            'process_id' => 'nullable|exists:processes,id',
            'sub_process' => 'nullable|string|max:255',
            'mode' => 'nullable|in:N,A',
            'aspect_number' => 'nullable|string|max:50',
            'activity' => 'sometimes|required|string|max:255',
            'aspect' => 'sometimes|required|string|max:255',
            'impact' => 'sometimes|required|string|max:255',
            'risk' => 'nullable|string',
            'existing_controls' => 'nullable|string',
            'gravity' => 'nullable|integer|min:1|max:5',
            'frequency' => 'nullable|integer|min:1|max:7',
            'sensitivity' => 'nullable|integer|min:1|max:5',
            'mastery' => 'nullable|integer|min:1|max:5',
            'significance_threshold' => 'nullable|integer',
            'additional_actions' => 'nullable|string',
            'responsible_id' => 'nullable|exists:users,id',
            'responsible_name' => 'nullable|string|max:255',
            'deadline' => 'nullable|date',
            'effectiveness_criterion' => 'nullable|string',
            'efficiency_criterion' => 'nullable|string',
            'observations' => 'nullable|string',
        ]);

        $validated['updated_by'] = $user->id;
        $aspect->update($validated);
        $aspect->load(['process:id,code,title', 'site:id,name', 'responsible:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Aspect environnemental mis à jour avec succès.',
            'data' => $aspect,
        ]);
    }

    /**
     * Remove the specified environmental aspect.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();
        $aspect = EnvironmentalAspect::where('enterprise_id', $user->enterprise_id)->findOrFail($id);
        $aspect->delete();

        return response()->json([
            'success' => true,
            'message' => 'Aspect environnemental supprimé avec succès.',
        ]);
    }

    /**
     * Get statistics on environmental aspects.
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = EnvironmentalAspect::where('enterprise_id', $user->enterprise_id);

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->query('site_id'));
        }

        $all = $query->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $all->count(),
                'significant' => $all->where('is_significant', true)->count(),
                'non_significant' => $all->where('is_significant', false)->count(),
                'mode_normal' => $all->where('mode', 'N')->count(),
                'mode_accidental' => $all->where('mode', 'A')->count(),
                'by_process' => $all->groupBy('process_id')->map->count(),
            ],
        ]);
    }
}

