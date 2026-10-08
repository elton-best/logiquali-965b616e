<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\SatisfactionSurveyResource;
use App\Models\SatisfactionSurvey;
use App\Models\Site;
use Illuminate\Http\Request;

class SatisfactionSurveyController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = SatisfactionSurvey::with('site');

        // Filter based on user type
        if ($user->isClientB()) {
            // Client B: only their own surveys (via created_by)
            $query->where('created_by', $user->id);
        } elseif ($user->enterprise_id) {
            // Client A: surveys from their enterprise's sites
            $siteIds = \App\Models\Site::where('enterprise_id', $user->enterprise_id)->pluck('id');
            $query->whereIn('site_id', $siteIds);
        }

        if ($request->filled('site_id')) {
            $query->where('site_id', (int) $request->input('site_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('year')) {
            $query->where('year', (int) $request->input('year'));
        }

        $perPage = $request->get('per_page', 20);
        $surveys = $query->latest()->paginate($perPage);

        // For Client B, return simple JSON format
        if ($user->isClientB()) {
            return response()->json([
                'data' => $surveys->items(),
                'total' => $surveys->total(),
                'current_page' => $surveys->currentPage(),
                'last_page' => $surveys->lastPage(),
                'per_page' => $surveys->perPage(),
            ]);
        }

        return SatisfactionSurveyResource::collection($surveys);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'type' => 'required|in:client,employee,supplier',
            'year' => 'required|integer|min:2020|max:2100',
            'period' => 'nullable|string',
            'respondent_name' => 'nullable|string|max:255',
            'respondent_email' => 'nullable|email',
            'responses' => 'required|array',
            'total_score' => 'nullable|numeric',
            'satisfaction_level' => 'nullable|in:very_satisfied,satisfied,moderately_satisfied,dissatisfied,very_dissatisfied',
            'recommendations' => 'nullable|string',
            'm13_d3_traceability' => 'nullable|array',
            'm13_d4_traceability' => 'nullable|array',
            'm13_d6_traceability' => 'nullable|array',
        ]);

        // For Client B, auto-fill created_by and respondent info
        if ($user->isClientB()) {
            $validated['created_by'] = $user->id;
            $validated['respondent_name'] = $validated['respondent_name'] ?? $user->name;
            $validated['respondent_email'] = $validated['respondent_email'] ?? $user->email;
        }

        if (!$this->canAccessSite($user, (int) $validated['site_id'])) {
            return response()->json([
                'message' => 'Accès refusé pour ce site.',
            ], 403);
        }

        $validated = $this->normalizeSurveyMetrics($validated);

        $survey = SatisfactionSurvey::create($validated);
        $survey->load('site');

        // For Client B, return simple JSON format
        if ($user->isClientB()) {
            return response()->json([
                'success' => true,
                'data' => $survey,
                'message' => 'Enquête créée avec succès',
            ], 201);
        }

        return new SatisfactionSurveyResource($survey);
    }

    public function show(SatisfactionSurvey $satisfactionSurvey)
    {
        if (!$this->canAccessSurvey(request()->user(), $satisfactionSurvey)) {
            return response()->json([
                'message' => 'Accès refusé pour cette fiche.',
            ], 403);
        }

        return new SatisfactionSurveyResource($satisfactionSurvey->load('site'));
    }

    public function update(Request $request, SatisfactionSurvey $satisfactionSurvey)
    {
        if (!$this->canAccessSurvey($request->user(), $satisfactionSurvey)) {
            return response()->json([
                'message' => 'Accès refusé pour cette fiche.',
            ], 403);
        }

        $validated = $request->validate([
            'site_id' => 'sometimes|exists:sites,id',
            'type' => 'sometimes|in:client,employee,supplier',
            'year' => 'sometimes|integer|min:2020|max:2100',
            'period' => 'nullable|string',
            'respondent_name' => 'nullable|string|max:255',
            'respondent_email' => 'nullable|email',
            'responses' => 'sometimes|array',
            'total_score' => 'nullable|numeric',
            'satisfaction_level' => 'nullable|in:very_satisfied,satisfied,moderately_satisfied,dissatisfied,very_dissatisfied',
            'recommendations' => 'nullable|string',
            'm13_d3_traceability' => 'nullable|array',
            'm13_d4_traceability' => 'nullable|array',
            'm13_d6_traceability' => 'nullable|array',
        ]);

        if (array_key_exists('site_id', $validated) && !$this->canAccessSite($request->user(), (int) $validated['site_id'])) {
            return response()->json([
                'message' => 'Accès refusé pour ce site.',
            ], 403);
        }

        $validated = $this->normalizeSurveyMetrics($validated, $satisfactionSurvey->responses ?? []);
        $satisfactionSurvey->update($validated);

        return new SatisfactionSurveyResource($satisfactionSurvey->load('site'));
    }

    public function destroy(SatisfactionSurvey $satisfactionSurvey)
    {
        if (!$this->canAccessSurvey(request()->user(), $satisfactionSurvey)) {
            return response()->json([
                'message' => 'Accès refusé pour cette fiche.',
            ], 403);
        }

        $satisfactionSurvey->delete();

        return response()->json(null, 204);
    }

    /**
     * Normalise total_score et satisfaction_level si non fournis explicitement.
     */
    private function normalizeSurveyMetrics(array $payload, array $existingResponses = []): array
    {
        $responses = $payload['responses'] ?? $existingResponses;
        if (!is_array($responses) || empty($responses)) {
            return $payload;
        }

        $numericValues = array_values(array_filter(array_map(static function ($value) {
            return is_numeric($value) ? (float) $value : null;
        }, $responses), static fn ($value) => $value !== null));

        if (count($numericValues) === 0) {
            return $payload;
        }

        $totalScore = array_sum($numericValues);
        if (!array_key_exists('total_score', $payload) || $payload['total_score'] === null || $payload['total_score'] === '') {
            $payload['total_score'] = $totalScore;
        }

        if (!array_key_exists('satisfaction_level', $payload) || $payload['satisfaction_level'] === null || $payload['satisfaction_level'] === '') {
            $maxValue = max($numericValues);
            $scaleMax = $maxValue <= 3 ? 3 : ($maxValue <= 4 ? 4 : 5);
            $maxScore = max(1, count($numericValues) * $scaleMax);
            $percentage = ($totalScore / $maxScore) * 100;

            $payload['satisfaction_level'] = match (true) {
                $percentage >= 85 => 'very_satisfied',
                $percentage >= 65 => 'satisfied',
                $percentage >= 45 => 'moderately_satisfied',
                $percentage >= 25 => 'dissatisfied',
                default => 'very_dissatisfied',
            };
        }

        return $payload;
    }

    private function canAccessSite($user, int $siteId): bool
    {
        if ($siteId <= 0) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        if ($user->isClientB()) {
            return (int) ($user->site_id ?? 0) === $siteId;
        }

        if ($user->enterprise_id) {
            return Site::query()
                ->where('enterprise_id', $user->enterprise_id)
                ->whereKey($siteId)
                ->exists();
        }

        return false;
    }

    private function canAccessSurvey($user, SatisfactionSurvey $survey): bool
    {
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        if ($user->isClientB()) {
            return (int) ($survey->created_by ?? 0) === (int) $user->id;
        }

        return $this->canAccessSite($user, (int) $survey->site_id);
    }
}
