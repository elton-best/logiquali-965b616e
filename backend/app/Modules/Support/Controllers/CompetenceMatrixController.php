<?php

namespace App\Modules\Support\Controllers;

use App\Models\Formation;

use App\Http\Controllers\Controller;
use App\Models\CompetenceAcquise;
use App\Models\CompetenceRequise;
use App\Models\JobDescription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CompetenceMatrixController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:support.competences.read')->only(['getMatrix', 'getGapAnalysis']);
        $this->middleware('permission:support.competences.update_tracking')->only(['getTrainingPlan', 'export']);
    }

    public function getMatrix(Request $request): JsonResponse
    {
        $query = User::with(['jobDescription.competencesRequises', 'competencesAcquises.competenceRequise'])
            ->whereNotNull('job_description_id');

        // Filters
        if ($request->filled('job_description_id')) {
            $query->where('job_description_id', $request->job_description_id);
        }

        if ($request->filled('competence_type')) {
            $query->whereHas('jobDescription.competencesRequises', function ($q) use ($request) {
                $q->where('competence_type', $request->competence_type);
            });
        }

        $users = $query->get();
        $matrix = [];

        foreach ($users as $user) {
            $userMatrix = [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'job_title' => $user->jobDescription?->job_title,
                'competences' => []
            ];

            if ($user->jobDescription) {
                foreach ($user->jobDescription->competencesRequises as $required) {
                    $acquired = $user->competencesAcquises
                        ->where('competence_requise_id', $required->id)
                        ->where('status', 'active')
                        ->first();

                    $userMatrix['competences'][] = [
                        'competence_id' => $required->id,
                        'competence_name' => $required->competence_name,
                        'competence_type' => $required->competence_type,
                        'level_required' => $required->level_required,
                        'priority' => $required->priority,
                        'level_acquired' => $acquired?->level_acquired,
                        'status' => $this->getCompetenceStatus($required, $acquired),
                        'expiry_date' => $acquired?->expiry_date,
                        'days_until_expiry' => $acquired?->getDaysUntilExpiry()
                    ];
                }
            }

            $matrix[] = $userMatrix;
        }

        return response()->json([
            'success' => true,
            'data' => $matrix
        ]);
    }

    public function getGapAnalysis(Request $request): JsonResponse
    {
        $gaps = [];
        $users = User::with(['jobDescription.competencesRequises', 'competencesAcquises.competenceRequise'])
            ->whereNotNull('job_description_id')
            ->get();

        foreach ($users as $user) {
            if (!$user->jobDescription) continue;

            $userGaps = [];
            foreach ($user->jobDescription->competencesRequises as $required) {
                $acquired = $user->competencesAcquises
                    ->where('competence_requise_id', $required->id)
                    ->where('status', 'active')
                    ->first();

                $status = $this->getCompetenceStatus($required, $acquired);
                
                if (in_array($status, ['missing', 'insufficient', 'expired'])) {
                    $userGaps[] = [
                        'competence_id' => $required->id,
                        'competence_name' => $required->competence_name,
                        'competence_type' => $required->competence_type,
                        'level_required' => $required->level_required,
                        'level_acquired' => $acquired?->level_acquired,
                        'priority' => $required->priority,
                        'gap_type' => $status,
                        'requires_certification' => $required->requires_certification,
                        'suggested_actions' => $this->getSuggestedActions($required, $acquired, $status)
                    ];
                }
            }

            if (!empty($userGaps)) {
                $gaps[] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'job_title' => $user->jobDescription->job_title,
                    'gaps' => $userGaps,
                    'gap_count' => count($userGaps),
                    'critical_gaps' => count(array_filter($userGaps, fn($gap) => $gap['priority'] === 'obligatoire'))
                ];
            }
        }

        // Sort by critical gaps count
        usort($gaps, fn($a, $b) => $b['critical_gaps'] <=> $a['critical_gaps']);

        return response()->json([
            'success' => true,
            'data' => $gaps,
            'summary' => [
                'users_with_gaps' => count($gaps),
                'total_gaps' => array_sum(array_column($gaps, 'gap_count')),
                'critical_gaps' => array_sum(array_column($gaps, 'critical_gaps'))
            ]
        ]);
    }

    public function getTrainingPlan(Request $request): JsonResponse
    {
        $gapAnalysis = $this->getGapAnalysis($request)->getData();
        $trainingPlan = [];

        foreach ($gapAnalysis->data as $userGap) {
            foreach ($userGap->gaps as $gap) {
                $competenceKey = $gap->competence_type . '_' . $gap->competence_name;
                
                if (!isset($trainingPlan[$competenceKey])) {
                    $trainingPlan[$competenceKey] = [
                        'competence_name' => $gap->competence_name,
                        'competence_type' => $gap->competence_type,
                        'level_required' => $gap->level_required,
                        'priority' => $gap->priority,
                        'requires_certification' => $gap->requires_certification,
                        'affected_users' => [],
                        'suggested_training' => $this->suggestTraining($gap),
                        'estimated_cost' => $this->estimateTrainingCost($gap),
                        'estimated_duration' => $this->estimateTrainingDuration($gap)
                    ];
                }

                $trainingPlan[$competenceKey]['affected_users'][] = [
                    'user_id' => $userGap->user_id,
                    'user_name' => $userGap->user_name,
                    'gap_type' => $gap->gap_type
                ];
            }
        }

        // Sort by priority and number of affected users
        uasort($trainingPlan, function($a, $b) {
            $priorityOrder = ['obligatoire' => 3, 'recommandee' => 2, 'optionnelle' => 1];
            $priorityA = $priorityOrder[$a['priority']] ?? 0;
            $priorityB = $priorityOrder[$b['priority']] ?? 0;
            
            if ($priorityA === $priorityB) {
                return count($b['affected_users']) <=> count($a['affected_users']);
            }
            return $priorityB <=> $priorityA;
        });

        return response()->json([
            'success' => true,
            'data' => array_values($trainingPlan),
            'summary' => [
                'total_trainings' => count($trainingPlan),
                'total_cost' => array_sum(array_column($trainingPlan, 'estimated_cost')),
                'total_users' => count(array_unique(array_merge(...array_map(
                    fn($plan) => array_column($plan['affected_users'], 'user_id'),
                    $trainingPlan
                ))))
            ]
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $matrix = $this->getMatrix($request)->getData()->data;
        $exportData = [];

        foreach ($matrix as $userMatrix) {
            foreach ($userMatrix->competences as $competence) {
                $exportData[] = [
                    'Employé' => $userMatrix->user_name,
                    'Poste' => $userMatrix->job_title,
                    'Compétence' => $competence->competence_name,
                    'Type' => $competence->competence_type,
                    'Niveau requis' => $competence->level_required,
                    'Niveau acquis' => $competence->level_acquired ?? 'Non acquis',
                    'Priorité' => $competence->priority,
                    'Statut' => $competence->status,
                    'Date expiration' => $competence->expiry_date,
                    'Jours restants' => $competence->days_until_expiry
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $exportData,
            'filename' => 'matrice_competences_' . now()->format('Y-m-d') . '.xlsx'
        ]);
    }

    private function getCompetenceStatus(CompetenceRequise $required, ?CompetenceAcquise $acquired): string
    {
        if (!$acquired) {
            return 'missing';
        }

        if ($acquired->isExpired()) {
            return 'expired';
        }

        if ($acquired->isExpiringSoon(30)) {
            return 'expiring_soon';
        }

        if (!$acquired->meetsRequiredLevel()) {
            return 'insufficient';
        }

        return 'compliant';
    }

    private function getSuggestedActions(CompetenceRequise $required, ?CompetenceAcquise $acquired, string $status): array
    {
        $actions = [];

        switch ($status) {
            case 'missing':
                $actions[] = $required->requires_certification 
                    ? 'Planifier formation certifiante'
                    : 'Planifier formation';
                break;
            case 'insufficient':
                $actions[] = 'Formation de perfectionnement';
                break;
            case 'expired':
                $actions[] = 'Renouvellement urgent';
                break;
        }

        return $actions;
    }

    private function suggestTraining($gap): string
    {
        if ($gap->requires_certification) {
            return "Formation certifiante {$gap->competence_name} - Niveau {$gap->level_required}";
        }
        
        return "Formation {$gap->competence_name} - Niveau {$gap->level_required}";
    }

    private function estimateTrainingCost($gap): int
    {
        $baseCost = $gap->requires_certification ? 1500 : 800;
        $levelMultiplier = ['base' => 1, 'intermediaire' => 1.3, 'avance' => 1.6, 'expert' => 2];
        
        return (int) ($baseCost * ($levelMultiplier[$gap->level_required] ?? 1));
    }

    private function estimateTrainingDuration($gap): int
    {
        $baseDuration = $gap->requires_certification ? 3 : 2; // days
        $levelMultiplier = ['base' => 1, 'intermediaire' => 1.5, 'avance' => 2, 'expert' => 3];
        
        return (int) ($baseDuration * ($levelMultiplier[$gap->level_required] ?? 1));
    }
}