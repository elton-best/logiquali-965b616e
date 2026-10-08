<?php

namespace App\Services;

use App\Models\Risk;
use Illuminate\Support\Facades\DB;

class RiskService
{
    public function create(array $data): Risk
    {
        return DB::transaction(function () use ($data) {
            $risk = Risk::create([
                'ref' => $this->generateReference($data['type'] ?? 'risk'),
                'title' => $data['title'] ?? null,
                'site_id' => $data['site_id'],
                'process_id' => $data['process_id'] ?? null,
                'type' => $data['type'] ?? 'risk',
                'category' => $data['category'] ?? null,
                'description' => $data['description'],
                'root_cause' => $data['root_cause'] ?? null,
                'probability' => $data['probability'] ?? 1,
                'gravity' => $data['gravity'] ?? 1,
                'impact' => $data['impact'] ?? 1,
                'responsible_id' => $data['responsible_id'] ?? null,
                'deadline' => $data['deadline'] ?? null,
            ]);

            // Calculer criticité
            $risk->calculateCriticality();

            // Sauvegarder évaluation initiale
            $risk->update([
                'initial_assessment' => [
                    'probability' => $risk->probability,
                    'gravity' => $risk->gravity,
                    'impact' => $risk->impact,
                    'criticality' => $risk->criticality,
                    'assessed_at' => now()->toDateTimeString(),
                    'assessed_by' => auth()->id(),
                ],
            ]);

            if (isset($data['axes'])) {
                $risk->syncAxes($data['axes']);
            }

            if (isset($data['processes'])) {
                $risk->processes()->sync($data['processes']);
            }

            activity()
                ->performedOn($risk)
                ->causedBy(auth()->user())
                ->log($risk->type === 'risk' ? 'Risque identifié' : 'Opportunité identifiée');

            return $risk->load(['axes', 'workflowState']);
        });
    }

    public function assess(Risk $risk, array $assessmentData): Risk
    {
        $risk->update([
            'probability' => $assessmentData['probability'],
            'gravity' => $assessmentData['gravity'] ?? $risk->gravity,
            'impact' => $assessmentData['impact'] ?? $risk->impact,
            'affected_stakeholders' => $assessmentData['affected_stakeholders'] ?? null,
        ]);

        $risk->calculateCriticality();

        if ($risk->canTransitionTo('assessed')) {
            $risk->transitionTo('assessed');
        }

        activity()
            ->performedOn($risk)
            ->causedBy(auth()->user())
            ->log("Évaluation effectuée : criticité = {$risk->criticality}");

        return $risk->fresh();
    }

    public function treat(Risk $risk, array $treatmentData): Risk
    {
        $risk->update([
            'treatment' => $treatmentData['treatment'],
            'treatment_plan' => $treatmentData['treatment_plan'] ?? null,
            'control_action' => $treatmentData['control_action'] ?? null,
            'prevention_measures' => $treatmentData['prevention_measures'] ?? null,
            'responsible_id' => $treatmentData['responsible_id'] ?? $risk->responsible_id,
            'deadline' => $treatmentData['deadline'] ?? $risk->deadline,
        ]);

        // Créer actions de traitement
        if (isset($treatmentData['actions'])) {
            foreach ($treatmentData['actions'] as $actionData) {
                $action = app(ActionService::class)->create($actionData);
                $risk->addAction($action);
            }
        }

        if ($risk->canTransitionTo('in_treatment')) {
            $risk->transitionTo('in_treatment');
        }

        activity()
            ->performedOn($risk)
            ->causedBy(auth()->user())
            ->log("Traitement défini : {$treatmentData['treatment']}");

        return $risk->fresh(['actions']);
    }

    public function reassess(Risk $risk, array $residualData): Risk
    {
        $risk->update([
            'residual_assessment' => [
                'probability' => $residualData['probability'],
                'gravity' => $residualData['gravity'] ?? $risk->gravity,
                'impact' => $residualData['impact'] ?? $risk->impact,
                'criticality' => $residualData['probability'] * ($risk->type === 'risk' ? $residualData['gravity'] : $residualData['impact']),
                'assessed_at' => now()->toDateTimeString(),
                'assessed_by' => auth()->id(),
            ],
            'last_review_date' => now(),
            'next_review_date' => $residualData['next_review_date'] ?? now()->addMonths(6),
        ]);

        activity()
            ->performedOn($risk)
            ->causedBy(auth()->user())
            ->log('Évaluation résiduelle effectuée');

        return $risk->fresh();
    }

    public function control(Risk $risk): Risk
    {
        if ($risk->canTransitionTo('controlled')) {
            $risk->transitionTo('controlled');
        }

        activity()
            ->performedOn($risk)
            ->causedBy(auth()->user())
            ->log('Risque maîtrisé');

        return $risk->fresh();
    }

    public function accept(Risk $risk, string $justification): Risk
    {
        if ($risk->canTransitionTo('accepted')) {
            $risk->transitionTo('accepted');
        }

        activity()
            ->performedOn($risk)
            ->causedBy(auth()->user())
            ->log("Risque accepté : {$justification}");

        return $risk->fresh();
    }

    public function validate(Risk $risk): Risk
    {
        $risk->update([
            'validated_by' => auth()->id(),
            'validation_date' => now(),
        ]);

        activity()
            ->performedOn($risk)
            ->causedBy(auth()->user())
            ->log('Risque validé');

        return $risk->fresh();
    }

    public function getMatrix(array $filters = []): array
    {
        $query = Risk::query()->where('type', 'risk');

        if (isset($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }

        if (isset($filters['axes'])) {
            $query->withAnyAxe($filters['axes']);
        }

        $risks = $query->get();

        // Matrice 4x4
        $matrix = [];
        for ($prob = 1; $prob <= 4; $prob++) {
            for ($grav = 1; $grav <= 4; $grav++) {
                $matrix[$prob][$grav] = $risks->filter(function ($risk) use ($prob, $grav) {
                    return $risk->probability == $prob && $risk->gravity == $grav;
                })->count();
            }
        }

        return [
            'matrix' => $matrix,
            'total' => $risks->count(),
            'high_priority' => $risks->filter->isHighRisk()->count(),
            'medium_priority' => $risks->filter->isMediumRisk()->count(),
        ];
    }

    public function getStatistics(array $filters = []): array
    {
        $query = Risk::query();

        if (isset($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }

        if (isset($filters['axes'])) {
            $query->withAnyAxe($filters['axes']);
        }

        $risks = $query->get();
        $opportunities = $query->where('type', 'opportunity')->get();

        return [
            'total_risks' => $risks->where('type', 'risk')->count(),
            'total_opportunities' => $opportunities->count(),
            'high_risks' => $risks->filter->isHighRisk()->count(),
            'medium_risks' => $risks->filter->isMediumRisk()->count(),
            'low_risks' => $risks->count() - $risks->filter->isHighRisk()->count() - $risks->filter->isMediumRisk()->count(),
            'by_category' => $risks->groupBy('category')->map->count(),
            'by_treatment' => $risks->groupBy('treatment')->map->count(),
            'needing_review' => $risks->filter->needsReview()->count(),
        ];
    }

    protected function generateReference(string $type): string
    {
        $prefix = $type === 'opportunity' ? 'OPP' : 'RSQ';
        $year = now()->year;
        $lastRisk = Risk::where('ref', 'like', "{$prefix}-{$year}-%")
            ->orderBy('ref', 'desc')
            ->first();

        if ($lastRisk) {
            $lastNumber = (int) substr($lastRisk->ref, -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%d-%03d', $prefix, $year, $newNumber);
    }
}
