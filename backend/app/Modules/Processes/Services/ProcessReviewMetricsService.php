<?php

namespace App\Modules\Processes\Services;

use App\Models\ProcessReview;

class ProcessReviewMetricsService
{
    public function compute(ProcessReview $review): array
    {
        $riskItems = $this->extractItems($review->risk_data);
        $opportunityItems = $this->extractItems($review->opportunity_data);
        $qualityActivities = $this->extractItems($review->quality_activities_data);
        $operationalActivities = $this->extractItems($review->operational_activities_data);
        $objectiveItems = $this->extractItems($review->quality_objectives_data);

        $riskCount = count($riskItems);
        $opportunityCount = count($opportunityItems);
        $riskOpportunityTotal = $riskCount + $opportunityCount;

        $riskRate = $riskOpportunityTotal > 0
            ? $this->round2(($riskCount / $riskOpportunityTotal) * 100)
            : 0.0;
        $opportunityRate = $riskOpportunityTotal > 0
            ? $this->round2(($opportunityCount / $riskOpportunityTotal) * 100)
            : 0.0;

        [$ncCount, $ncBaseCount, $ncRate] = $this->computeNonConformityRate($review);
        [$plannedActionsCount, $executionRate] = $this->computeExecutionRate($qualityActivities, $operationalActivities);
        [$objectivesCount, $objectivesAverage] = $this->computeObjectivesAverage($objectiveItems);

        return [
            'risk_opportunity' => [
                'risk_count' => $riskCount,
                'opportunity_count' => $opportunityCount,
                'risk_rate' => $riskRate,
                'opportunity_rate' => $opportunityRate,
            ],
            'non_conformity' => [
                'nc_count' => $ncCount,
                'base_count' => $ncBaseCount,
                'nc_rate' => $ncRate,
            ],
            'execution' => [
                'planned_actions_count' => $plannedActionsCount,
                'execution_rate' => $executionRate,
            ],
            'objectives' => [
                'objectives_count' => $objectivesCount,
                'performance_average' => $objectivesAverage,
            ],
        ];
    }

    private function extractItems(mixed $sectionData): array
    {
        if (!is_array($sectionData)) {
            return [];
        }

        $items = $sectionData['items'] ?? null;
        if (!is_array($items)) {
            return [];
        }

        return array_values(array_filter($items, static fn ($item): bool => is_array($item)));
    }

    private function computeNonConformityRate(ProcessReview $review): array
    {
        $complianceData = is_array($review->compliance_data) ? $review->compliance_data : [];
        $complianceItems = $this->extractItems($complianceData);
        $nonConformityItems = $this->extractItems($review->non_conformity_data);

        $explicitRate = $this->normalizeNumeric($complianceData['nc_rate'] ?? null);
        if ($explicitRate !== null) {
            $baseCount = max(count($complianceItems), count($nonConformityItems));
            $rate = $this->round2($this->clampPercentage($explicitRate));
            $estimatedNc = $baseCount > 0
                ? $this->round2(($rate / 100) * $baseCount)
                : (float) count($nonConformityItems);

            return [$estimatedNc, $baseCount, $rate];
        }

        if (count($complianceItems) > 0) {
            $nonConformityCount = 0;
            foreach ($complianceItems as $item) {
                if ($this->isNonConformityItem($item)) {
                    $nonConformityCount++;
                }
            }

            $baseCount = count($complianceItems);
            $rate = $baseCount > 0
                ? $this->round2(($nonConformityCount / $baseCount) * 100)
                : 0.0;

            return [(float) $nonConformityCount, $baseCount, $rate];
        }

        if (count($nonConformityItems) > 0) {
            $count = count($nonConformityItems);

            return [(float) $count, $count, 100.0];
        }

        return [0.0, 0, 0.0];
    }

    private function isNonConformityItem(array $item): bool
    {
        $statusKeys = ['conformity_status', 'status', 'compliance_status'];
        foreach ($statusKeys as $key) {
            $value = strtolower(trim((string) ($item[$key] ?? '')));
            if ($value === '') {
                continue;
            }

            if (in_array($value, ['non_conforme', 'non-conforme', 'non conform', 'nonconforme', 'non_compliant'], true)) {
                return true;
            }
        }

        if (array_key_exists('compliant', $item)) {
            return filter_var($item['compliant'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === false;
        }

        if (array_key_exists('is_compliant', $item)) {
            return filter_var($item['is_compliant'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === false;
        }

        return false;
    }

    private function computeExecutionRate(array $qualityActivities, array $operationalActivities): array
    {
        $allActivities = array_merge($qualityActivities, $operationalActivities);
        $count = count($allActivities);

        if ($count === 0) {
            return [0, 0.0];
        }

        $sum = 0.0;
        foreach ($allActivities as $activity) {
            $sum += $this->computeActivityProgress($activity);
        }

        return [$count, $this->round2($sum / $count)];
    }

    private function computeActivityProgress(array $item): float
    {
        $numericProgressKeys = ['progress', 'progress_percent', 'taux_execution', 'completion_rate'];
        foreach ($numericProgressKeys as $key) {
            $value = $this->normalizeNumeric($item[$key] ?? null);
            if ($value !== null) {
                return $this->clampPercentage($value);
            }
        }

        $status = strtolower(trim((string) ($item['status'] ?? '')));
        if ($status === '') {
            return 0.0;
        }

        if (in_array($status, ['completed', 'done', 'closed', 'realise', 'termine'], true)) {
            return 100.0;
        }

        if (in_array($status, ['in_progress', 'in-progress', 'en_cours', 'ongoing'], true)) {
            return 50.0;
        }

        return 0.0;
    }

    private function computeObjectivesAverage(array $objectiveItems): array
    {
        if ($objectiveItems === []) {
            return [0, 0.0];
        }

        $scores = [];
        foreach ($objectiveItems as $item) {
            $score = $this->computeObjectiveScore($item);
            if ($score !== null) {
                $scores[] = $score;
            }
        }

        if ($scores === []) {
            return [count($objectiveItems), 0.0];
        }

        return [count($objectiveItems), $this->round2(array_sum($scores) / count($scores))];
    }

    private function computeObjectiveScore(array $item): ?float
    {
        $directKeys = ['achievement_rate', 'taux_realisation', 'performance_rate'];
        foreach ($directKeys as $key) {
            $value = $this->normalizeNumeric($item[$key] ?? null);
            if ($value !== null) {
                return $this->clampPercentage($value);
            }
        }

        $score = $this->normalizeNumeric($item['score'] ?? null);
        if ($score !== null) {
            return $score <= 5 ? $this->clampPercentage($score * 20) : $this->clampPercentage($score);
        }

        $pairs = [
            ['achieved_value', 'target_value'],
            ['current_value', 'target_value'],
            ['value', 'target'],
            ['result', 'target'],
            ['actual', 'target'],
        ];

        foreach ($pairs as [$numeratorKey, $denominatorKey]) {
            $numerator = $this->normalizeNumeric($item[$numeratorKey] ?? null);
            $denominator = $this->normalizeNumeric($item[$denominatorKey] ?? null);

            if ($numerator !== null && $denominator !== null && $denominator > 0) {
                return $this->clampPercentage(($numerator / $denominator) * 100);
            }
        }

        return null;
    }

    private function normalizeNumeric(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = str_replace(',', '.', trim($value));
        if ($normalized === '' || !is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    private function clampPercentage(float $value): float
    {
        if ($value < 0) {
            return 0.0;
        }
        if ($value > 100) {
            return 100.0;
        }

        return $value;
    }

    private function round2(float $value): float
    {
        return round($value, 2);
    }
}
