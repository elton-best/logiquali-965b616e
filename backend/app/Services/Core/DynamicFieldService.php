<?php

namespace App\Services\Core;

use App\Models\EvaluationCriteria;
use App\Models\EvaluationRequest;
use Illuminate\Support\Collection;

class DynamicFieldService
{
    public function getEvaluationCriteria(
        int $enterpriseId,
        string $formType,
        ?array $criteriaIds = null,
        bool $onlyActive = true
    ): Collection {
        $query = EvaluationCriteria::where('enterprise_id', $enterpriseId)
            ->where('form_type', $formType);

        if ($onlyActive) {
            $query->active();
        }

        if (is_array($criteriaIds) && count($criteriaIds) > 0) {
            $query->whereIn('id', array_values(array_unique($criteriaIds)));
        }

        return $query->ordered()->get();
    }

    public function syncRequestCriteriaSnapshot(EvaluationRequest $evaluationRequest, ?array $criteriaIds = null): void
    {
        $criteria = $this->getEvaluationCriteria(
            $evaluationRequest->enterprise_id,
            $evaluationRequest->type,
            $criteriaIds,
            true
        );

        $syncPayload = [];

        foreach ($criteria as $index => $criterion) {
            $syncPayload[$criterion->id] = [
                'criterion_name' => $criterion->name,
                'criterion_code' => $criterion->code,
                'criterion_description' => $criterion->description,
                'criterion_category' => $criterion->category,
                'scale_type' => $criterion->scale_type,
                'scale_min' => $criterion->scale_min,
                'scale_max' => $criterion->scale_max,
                'scale_labels' => $criterion->scale_labels ? json_encode($criterion->scale_labels) : null,
                'weight' => $criterion->weight,
                'is_mandatory' => $criterion->is_mandatory,
                'display_order' => $criterion->display_order ?? $index,
            ];
        }

        $evaluationRequest->criteria()->sync($syncPayload);
    }

    public function resolveRequestCriteria(EvaluationRequest $evaluationRequest): Collection
    {
        $criteria = $evaluationRequest->criteria()->get();

        if ($criteria->isEmpty()) {
            return $this->getEvaluationCriteria($evaluationRequest->enterprise_id, $evaluationRequest->type);
        }

        return $criteria->map(function (EvaluationCriteria $criterion) {
            $labels = $criterion->pivot->scale_labels;

            if (is_string($labels)) {
                $decoded = json_decode($labels, true);
                $labels = is_array($decoded) ? $decoded : null;
            }

            $criterion->name = $criterion->pivot->criterion_name ?? $criterion->name;
            $criterion->code = $criterion->pivot->criterion_code ?? $criterion->code;
            $criterion->description = $criterion->pivot->criterion_description ?? $criterion->description;
            $criterion->category = $criterion->pivot->criterion_category ?? $criterion->category;
            $criterion->scale_type = $criterion->pivot->scale_type ?? $criterion->scale_type;
            $criterion->scale_min = $criterion->pivot->scale_min ?? $criterion->scale_min;
            $criterion->scale_max = $criterion->pivot->scale_max ?? $criterion->scale_max;
            $criterion->scale_labels = $labels;
            $criterion->weight = $criterion->pivot->weight ?? $criterion->weight;
            $criterion->is_mandatory = $criterion->pivot->is_mandatory ?? $criterion->is_mandatory;
            $criterion->display_order = $criterion->pivot->display_order ?? $criterion->display_order;

            return $criterion;
        })->sortBy('display_order')->values();
    }

    /**
     * Shared mandatory dynamic fields for internal audits.
     */
    public function getInternalAuditRequiredFields(): array
    {
        return ['planned_date', 'assigned_to', 'objectives', 'reference_documents'];
    }
}
