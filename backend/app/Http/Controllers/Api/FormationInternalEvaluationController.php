<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Formation;
use App\Models\FormationInternalEvaluation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormationInternalEvaluationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:support.competences.read')->only(['show']);
        $this->middleware('permission:support.competences.update')->only(['upsert', 'destroy']);
    }

    public function show(Formation $formation): JsonResponse
    {
        $evaluation = $formation->internalEvaluation;

        if (!$evaluation) {
            return response()->json(null, 204);
        }

        return response()->json($this->serialize($evaluation));
    }

    public function upsert(Request $request, Formation $formation): JsonResponse
    {
        if ($formation->status !== Formation::STATUS_REALISEE) {
            return response()->json([
                'message' => 'La fiche d\'évaluation interne est autorisée uniquement pour une formation réalisée'
            ], 422);
        }

        $validated = $request->validate([
            'method' => 'nullable|in:combinaison',
            'scores' => 'nullable|array',
            'scores.pedagogie' => 'nullable|integer|min:1|max:5',
            'scores.contenu' => 'nullable|integer|min:1|max:5',
            'scores.applicabilite' => 'nullable|integer|min:1|max:5',
            'scores.animation' => 'nullable|integer|min:1|max:5',
            'global_score' => 'nullable|numeric|min:0|max:5',
            'strengths' => 'nullable|array',
            'strengths.*' => 'string|max:255',
            'improvements' => 'nullable|array',
            'improvements.*' => 'string|max:255',
            'comment' => 'nullable|string',
            'evaluated_at' => 'nullable|date',
        ]);

        $globalScore = $validated['global_score'] ?? null;
        if ($globalScore === null && isset($validated['scores']) && is_array($validated['scores']) && count($validated['scores']) > 0) {
            $scoreValues = array_filter($validated['scores'], fn ($value) => is_numeric($value));
            if (count($scoreValues) > 0) {
                $globalScore = round(array_sum($scoreValues) / count($scoreValues), 2);
            }
        }

        $evaluation = FormationInternalEvaluation::query()->updateOrCreate(
            ['formation_id' => $formation->id],
            [
                'evaluator_id' => $request->user()?->id,
                'method' => $validated['method'] ?? 'combinaison',
                'scores' => $validated['scores'] ?? null,
                'global_score' => $globalScore,
                'strengths' => $validated['strengths'] ?? null,
                'improvements' => $validated['improvements'] ?? null,
                'comment' => $validated['comment'] ?? null,
                'evaluated_at' => $validated['evaluated_at'] ?? now()->toDateString(),
            ]
        );

        return response()->json($this->serialize($evaluation->fresh()), 201);
    }

    public function destroy(Formation $formation): JsonResponse
    {
        $evaluation = $formation->internalEvaluation;
        if (!$evaluation) {
            return response()->json(null, 204);
        }

        $evaluation->delete();

        return response()->json(null, 204);
    }

    private function serialize(FormationInternalEvaluation $evaluation): array
    {
        return [
            'id' => $evaluation->id,
            'formation_id' => $evaluation->formation_id,
            'evaluator_id' => $evaluation->evaluator_id,
            'method' => $evaluation->method,
            'scores' => $evaluation->scores,
            'global_score' => $evaluation->global_score !== null ? (float) $evaluation->global_score : null,
            'strengths' => $evaluation->strengths,
            'improvements' => $evaluation->improvements,
            'comment' => $evaluation->comment,
            'evaluated_at' => $evaluation->evaluated_at?->toDateString(),
            'created_at' => $evaluation->created_at?->toISOString(),
            'updated_at' => $evaluation->updated_at?->toISOString(),
        ];
    }
}
