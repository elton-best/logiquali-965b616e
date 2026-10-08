<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImprovementSuggestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImprovementSuggestionController extends Controller
{
    protected function canManageSuggestions($user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->isSuperAdmin()
            || $user->isEnterpriseAdmin()
            || $user->isSiteManager();
    }

    public function index(Request $request): JsonResponse
    {
        $query = ImprovementSuggestion::with(['site', 'process', 'proposer']);
        $user = $request->user();
        $canManageAll = $this->canManageSuggestions($user);
        $scope = (string) $request->string('scope', $canManageAll ? 'site' : 'mine');

        if (!$canManageAll && $user) {
            $query->where('proposer_id', $user->id);
        } elseif ($scope === 'mine' && $user) {
            $query->where('proposer_id', $user->id);
        }

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->integer('site_id'));
        } elseif ($user && $user->site_id) {
            $query->where('site_id', $user->site_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('impact')) {
            $query->where('impact', $request->string('impact'));
        }

        if ($request->filled('process_id')) {
            $query->where('process_id', $request->integer('process_id'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('ref', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $items = $query->latest('proposed_at')->latest('id')->paginate((int) $request->get('per_page', 20));

        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $canManageAll = $this->canManageSuggestions($user);

        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'process_id' => 'nullable|exists:processes,id',
            'proposer_id' => 'nullable|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'impact' => 'nullable|in:low,medium,high',
            'status' => 'nullable|in:pending,in_progress,adopted,rejected',
            'follow_up' => 'nullable|string|max:5000',
            'proposed_at' => 'nullable|date',
        ]);

        $proposerId = $canManageAll
            ? ($validated['proposer_id'] ?? $user?->id)
            : $user?->id;

        $suggestion = ImprovementSuggestion::create([
            ...$validated,
            'proposer_id' => $proposerId,
            'impact' => $validated['impact'] ?? 'medium',
            'status' => $validated['status'] ?? 'pending',
            'proposed_at' => $validated['proposed_at'] ?? now()->toDateString(),
        ]);

        return response()->json($suggestion->load(['site', 'process', 'proposer']), 201);
    }

    public function show(int $id): JsonResponse
    {
        $suggestion = ImprovementSuggestion::with(['site', 'process', 'proposer'])->findOrFail($id);

        $user = request()->user();
        if (!$this->canManageSuggestions($user) && (int) $suggestion->proposer_id !== (int) $user?->id) {
            abort(403, 'Vous ne pouvez consulter que vos propres suggestions.');
        }

        return response()->json($suggestion);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $suggestion = ImprovementSuggestion::findOrFail($id);
        $user = $request->user();
        $canManageAll = $this->canManageSuggestions($user);

        if (!$canManageAll && (int) $suggestion->proposer_id !== (int) $user?->id) {
            abort(403, 'Vous ne pouvez modifier que vos propres suggestions.');
        }

        $validated = $request->validate([
            'process_id' => 'nullable|exists:processes,id',
            'proposer_id' => 'nullable|exists:users,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:5000',
            'impact' => 'sometimes|in:low,medium,high',
            'status' => 'sometimes|in:pending,in_progress,adopted,rejected',
            'follow_up' => 'nullable|string|max:5000',
            'proposed_at' => 'nullable|date',
        ]);

        if (!$canManageAll) {
            unset($validated['proposer_id'], $validated['status'], $validated['follow_up']);
        }

        $suggestion->update($validated);
        return response()->json($suggestion->load(['site', 'process', 'proposer']));
    }

    public function destroy(int $id): JsonResponse
    {
        $suggestion = ImprovementSuggestion::findOrFail($id);
        $user = request()->user();

        if (!$this->canManageSuggestions($user) && (int) $suggestion->proposer_id !== (int) $user?->id) {
            abort(403, 'Vous ne pouvez supprimer que vos propres suggestions.');
        }

        $suggestion->delete();

        return response()->json(['message' => 'Suggestion supprimée'], 200);
    }
}
