<?php

namespace App\Modules\Planning\Services;

use App\Models\ProcessObjective;
use Illuminate\Support\Collection;

class ObjectiveCalculationService
{
    /**
     * Calcule le taux d'atteinte actuel d'un objectif selon les règles §8 du CDC.
     *
     * RÈGLES MATHÉMATIQUES :
     * 1. Mois > M : ignorés (même si saisis).
     * 2. Mois N/A : ignorés (exclus du calcul et du dénominateur).
     * 3. Mois <= M vides : ignorés (non renseignés).
     * 4. Si aucun mois éligible renseigné : renvoie null (affiché "—" en front).
     * 5. Plafonnement strict à 100 % sur chaque mois et sur la moyenne finale.
     *
     * @param array|null $realizations Liste des valeurs mensuelles/périodiques
     * @param string $frequency 'monthly', 'quarterly', 'semiannual', 'annual'
     * @param int|null $currentMonth Mois de référence 1..12 (défaut : mois calendaire actuel)
     * @return float|null Taux d'atteinte arrondi à 2 décimales ou null si aucun mois éligible
     */
    public function calculateCurrentRate(
        ?array $realizations,
        string $frequency = 'monthly',
        ?int $currentMonth = null
    ): ?float {
        if (empty($realizations)) {
            return null;
        }

        $currentMonth = $currentMonth ?? (int) now()->format('n');
        $maxEligibleIndex = $this->getMaxEligibleIndex($frequency, $currentMonth);

        $eligibleValues = [];

        foreach ($realizations as $index => $item) {
            $index = (int) $index;

            // 1. Les périodes futures sont strictement ignorées
            if ($index > $maxEligibleIndex) {
                continue;
            }

            // Extraction de la valeur et du flag N/A
            $parsed = $this->parsePeriodValue($item);

            // 2. Si non applicable (N/A), exclu du calcul
            if ($parsed['is_na']) {
                continue;
            }

            // 3. Si vide / non renseigné, exclu du calcul
            if ($parsed['value'] === null) {
                continue;
            }

            // 4. Plafonnement strict : 0 <= valeur <= 100 %
            $val = min(100.0, max(0.0, (float) $parsed['value']));
            $eligibleValues[] = $val;
        }

        // Si aucun mois éligible n'a été renseigné
        if (count($eligibleValues) === 0) {
            return null;
        }

        $average = array_sum($eligibleValues) / count($eligibleValues);

        // Plafonnement final à 100.0 %
        return round(min(100.0, max(0.0, $average)), 2);
    }

    /**
     * Vérifie si la période d'un mois est verrouillée (REQ-6.2-06).
     * Après le dernier jour du mois calendaire, la saisie devient en lecture seule.
     *
     * @param int $monthNumber 1..12
     * @param int|null $year Année (défaut : année courante)
     * @param mixed $user Utilisateur actuel (pour vérifier les exceptions de rôle)
     * @return bool
     */
    public function isMonthLocked(int $monthNumber, ?int $year = null, $user = null): bool
    {
        $currentYear = (int) now()->format('Y');
        $currentMonth = (int) now()->format('n');
        $year = $year ?? $currentYear;

        // Exception pour administrateurs ou RQ si nécessaire
        if ($user && method_exists($user, 'hasAnyRole')) {
            if ($user->hasAnyRole(['admin', 'superadmin', 'responsable_qualite', 'rq'])) {
                // Peut être autorisé à modifier en cas d'exception de revue
                return false;
            }
        }

        if ($year < $currentYear) {
            return true;
        }

        if ($year === $currentYear) {
            return $monthNumber < $currentMonth;
        }

        // Année future : pas encore verrouillée (mais future)
        return false;
    }

    /**
     * Synthèse par processus : moyenne des taux d'atteinte actuels des objectifs du processus.
     */
    public function calculateProcessSummary(iterable $objectives, ?int $currentMonth = null): array
    {
        $grouped = collect($objectives)->groupBy(function ($obj) {
            return $obj->process_id ?? ($obj['process_id'] ?? 0);
        });

        $summaries = [];

        foreach ($grouped as $processId => $items) {
            $rates = [];
            $processName = null;

            foreach ($items as $item) {
                if ($processName === null) {
                    $processName = $this->extractProcessName($item, $processId);
                }
                $rate = $this->resolveObjectiveRate($item, $currentMonth);
                if ($rate !== null) {
                    $rates[] = $rate;
                }
            }

            $count = count($items);
            $avgRate = count($rates) > 0 ? round(array_sum($rates) / count($rates), 2) : null;

            $summaries[] = [
                'process_id' => $processId,
                'process_name' => $processName,
                'total_objectives' => $count,
                'evaluated_objectives' => count($rates),
                'average_rate' => $avgRate,
                'achieved_count' => collect($rates)->filter(fn($r) => $r >= 80.0)->count(),
                'warning_count' => collect($rates)->filter(fn($r) => $r < 50.0)->count(),
            ];
        }

        return $summaries;
    }

    /**
     * Synthèse par axe stratégique (§8 / REQ-6.2-07).
     * Les objectifs multi-axes comptent dans chacun de leurs axes.
     */
    public function calculateStrategicAxisSummary(iterable $objectives, ?int $currentMonth = null): array
    {
        $axisBuckets = [];

        foreach ($objectives as $item) {
            $rate = $this->resolveObjectiveRate($item, $currentMonth);
            $axes = $this->extractAxes($item);

            if (empty($axes)) {
                $axes = ['Non rattaché'];
            }

            foreach ($axes as $axis) {
                $axisKey = trim((string) $axis);
                if ($axisKey === '') {
                    $axisKey = 'Non rattaché';
                }

                if (!isset($axisBuckets[$axisKey])) {
                    $axisBuckets[$axisKey] = [
                        'axis' => $axisKey,
                        'total_objectives' => 0,
                        'rates' => [],
                    ];
                }

                $axisBuckets[$axisKey]['total_objectives']++;
                if ($rate !== null) {
                    $axisBuckets[$axisKey]['rates'][] = $rate;
                }
            }
        }

        $result = [];
        foreach ($axisBuckets as $axisKey => $data) {
            $rates = $data['rates'];
            $avg = count($rates) > 0 ? round(array_sum($rates) / count($rates), 2) : null;

            $result[] = [
                'axis' => $axisKey,
                'total_objectives' => $data['total_objectives'],
                'evaluated_objectives' => count($rates),
                'average_rate' => $avg,
                'achieved_count' => collect($rates)->filter(fn($r) => $r >= 80.0)->count(),
                'warning_count' => collect($rates)->filter(fn($r) => $r < 50.0)->count(),
            ];
        }

        return $result;
    }

    /**
     * Synthèse par norme (§8 / REQ-6.2-07).
     * Un objectif multi-normes compte dans chacune de ses normes.
     */
    public function calculateNormSummary(iterable $objectives, ?int $currentMonth = null): array
    {
        $normBuckets = [];

        foreach ($objectives as $item) {
            $rate = $this->resolveObjectiveRate($item, $currentMonth);
            $norms = $this->extractNorms($item);

            if (empty($norms)) {
                $norms = ['Sans norme spécifiée'];
            }

            foreach ($norms as $norm) {
                $normKey = trim((string) $norm);
                if ($normKey === '') {
                    $normKey = 'Sans norme spécifiée';
                }

                if (!isset($normBuckets[$normKey])) {
                    $normBuckets[$normKey] = [
                        'norm' => $normKey,
                        'total_objectives' => 0,
                        'rates' => [],
                    ];
                }

                $normBuckets[$normKey]['total_objectives']++;
                if ($rate !== null) {
                    $normBuckets[$normKey]['rates'][] = $rate;
                }
            }
        }

        $result = [];
        foreach ($normBuckets as $normKey => $data) {
            $rates = $data['rates'];
            $avg = count($rates) > 0 ? round(array_sum($rates) / count($rates), 2) : null;

            $result[] = [
                'norm' => $normKey,
                'total_objectives' => $data['total_objectives'],
                'evaluated_objectives' => count($rates),
                'average_rate' => $avg,
                'achieved_count' => collect($rates)->filter(fn($r) => $r >= 80.0)->count(),
                'warning_count' => collect($rates)->filter(fn($r) => $r < 50.0)->count(),
            ];
        }

        return $result;
    }

    /**
     * Synthèse globale du système (§8 / REQ-6.2-07).
     * Regroupe : Taux global, Total, Conformes (>=80%), En vigilance (<50%),
     * et les synthèses détaillées (processus, axes, normes).
     */
    public function calculateSystemSummary(iterable $objectives, ?int $currentMonth = null): array
    {
        $objectivesList = collect($objectives);
        $allRates = [];

        foreach ($objectivesList as $item) {
            $rate = $this->resolveObjectiveRate($item, $currentMonth);
            if ($rate !== null) {
                $allRates[] = $rate;
            }
        }

        $totalCount = $objectivesList->count();
        $evaluatedCount = count($allRates);
        $systemAverageRate = $evaluatedCount > 0 ? round(array_sum($allRates) / $evaluatedCount, 2) : null;

        $achievedCount = collect($allRates)->filter(fn($r) => $r >= 80.0)->count();
        $warningCount = collect($allRates)->filter(fn($r) => $r < 50.0)->count();

        return [
            'system_average_rate' => $systemAverageRate,
            'total_objectives' => $totalCount,
            'evaluated_objectives' => $evaluatedCount,
            'achieved_count' => $achievedCount,
            'warning_count' => $warningCount,
            'current_month' => $currentMonth ?? (int) now()->format('n'),
            'by_process' => $this->calculateProcessSummary($objectivesList, $currentMonth),
            'by_axis' => $this->calculateStrategicAxisSummary($objectivesList, $currentMonth),
            'by_norm' => $this->calculateNormSummary($objectivesList, $currentMonth),
        ];
    }

    /**
     * Extrait et normalise la valeur d'une période (mois).
     */
    public function parsePeriodValue(mixed $item): array
    {
        if ($item === null || $item === '') {
            return ['value' => null, 'is_na' => false];
        }

        // Cas objet / tableau associatif : ['value' => 85, 'is_na' => false]
        if (is_array($item)) {
            $isNa = !empty($item['is_na']) || in_array(strtoupper((string) ($item['value'] ?? '')), ['NA', 'N/A'], true);
            if ($isNa) {
                return ['value' => null, 'is_na' => true];
            }
            $val = $item['value'] ?? null;
            return [
                'value' => (is_numeric($val) && $val !== '') ? (float) $val : null,
                'is_na' => false,
            ];
        }

        // Cas chaîne ou valeur directe
        $strVal = strtoupper(trim((string) $item));
        if ($strVal === 'NA' || $strVal === 'N/A') {
            return ['value' => null, 'is_na' => true];
        }

        if (is_numeric($item)) {
            return ['value' => (float) $item, 'is_na' => false];
        }

        return ['value' => null, 'is_na' => false];
    }

    /**
     * Détermine l'index maximum éligible selon la fréquence et le mois actuel.
     */
    private function getMaxEligibleIndex(string $frequency, int $currentMonth): int
    {
        return match ($frequency) {
            'quarterly' => (int) floor(($currentMonth - 1) / 3),
            'semiannual' => (int) floor(($currentMonth - 1) / 6),
            'annual' => 0,
            default => $currentMonth - 1, // 'monthly' : 0 = Janvier, 11 = Décembre
        };
    }

    /**
     * Résout le taux actuel d'un objectif (depuis un modèle ou un array).
     */
    private function resolveObjectiveRate(mixed $objective, ?int $currentMonth = null): ?float
    {
        $realizations = null;
        $frequency = 'monthly';

        if (is_object($objective)) {
            $realizations = $objective->period_realizations ?? null;
            $frequency = $objective->measurement_frequency ?? 'monthly';
            // Si déjà calculé en mémoire
            if (isset($objective->current_achievement_rate)) {
                return $objective->current_achievement_rate;
            }
            if (isset($objective->taux_actuel)) {
                return $objective->taux_actuel;
            }
        } elseif (is_array($objective)) {
            $realizations = $objective['period_realizations'] ?? null;
            $frequency = $objective['measurement_frequency'] ?? 'monthly';
            if (isset($objective['current_achievement_rate'])) {
                return $objective['current_achievement_rate'];
            }
            if (isset($objective['taux_actuel'])) {
                return $objective['taux_actuel'];
            }
        }

        return $this->calculateCurrentRate($realizations, $frequency, $currentMonth);
    }

    /**
     * Extrait les axes stratégiques d'un objectif.
     */
    private function extractAxes(mixed $objective): array
    {
        if (is_object($objective)) {
            if (!empty($objective->strategic_axes) && is_array($objective->strategic_axes)) {
                return $objective->strategic_axes;
            }
            if (!empty($objective->strategic_axis)) {
                return [$objective->strategic_axis];
            }
            if (!empty($objective->strategicAxis?->name)) {
                return [$objective->strategicAxis->name];
            }
        } elseif (is_array($objective)) {
            if (!empty($objective['strategic_axes']) && is_array($objective['strategic_axes'])) {
                return $objective['strategic_axes'];
            }
            if (!empty($objective['strategic_axis'])) {
                return [$objective['strategic_axis']];
            }
        }

        return [];
    }

    /**
     * Extrait les normes applicables d'un objectif.
     */
    private function extractNorms(mixed $objective): array
    {
        if (is_object($objective)) {
            if (!empty($objective->applicable_norms) && is_array($objective->applicable_norms)) {
                return $objective->applicable_norms;
            }
            if (!empty($objective->process?->normes_iso) && is_array($objective->process->normes_iso)) {
                return $objective->process->normes_iso;
            }
        } elseif (is_array($objective)) {
            if (!empty($objective['applicable_norms']) && is_array($objective['applicable_norms'])) {
                return $objective['applicable_norms'];
            }
            if (!empty($objective['process']['normes_iso']) && is_array($objective['process']['normes_iso'])) {
                return $objective['process']['normes_iso'];
            }
        }

        return [];
    }

    private function extractProcessName(mixed $item, mixed $processId): string
    {
        if (is_object($item)) {
            return $item->process_name 
                ?? $item->process->name 
                ?? $item->process->title 
                ?? "Processus #{$processId}";
        }
        if (is_array($item)) {
            return $item['process_name'] 
                ?? $item['process']['name'] 
                ?? $item['process']['title'] 
                ?? "Processus #{$processId}";
        }
        return "Processus #{$processId}";
    }
}
