<?php

namespace App\Http\Resources;

use App\Services\ProcessReviewMetricsService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcessReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'process_id' => $this->process_id,
            'title' => $this->title,
            'type' => $this->type,
            'review_date' => $this->review_date?->format('Y-m-d'),
            'version_reviewed' => $this->version_reviewed,
            'status' => $this->status,
            'led_by' => $this->led_by,
            'closed_by' => $this->closed_by,
            'coverage_start_date' => $this->coverage_start_date?->format('Y-m-d'),
            'coverage_end_date' => $this->coverage_end_date?->format('Y-m-d'),
            'started_at' => $this->started_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'participants' => $this->participants ?? [],
            'participants_presence' => $this->participants_presence ?? [],
            'role_assignments' => $this->role_assignments ?? [],
            'action_responsibles' => $this->action_responsibles ?? [],
            'other_participants' => $this->other_participants,
            'objectives' => $this->objectives,
            'findings' => $this->findings,
            'strengths' => $this->strengths,
            'weaknesses' => $this->weaknesses,
            'opportunities_improvement' => $this->opportunities_improvement,
            'decision' => $this->decision,
            'decision_comment' => $this->decision_comment,
            'action_items' => $this->action_items ?? [],
            'next_review_date' => $this->next_review_date?->format('Y-m-d'),
            'attachments' => $this->attachments ?? [],
            'synthesis_data' => $this->synthesis_data ?? [],
            'pip_data' => $this->pip_data ?? [],
            'risk_data' => $this->risk_data ?? [],
            'opportunity_data' => $this->opportunity_data ?? [],
            'quality_objectives_data' => $this->quality_objectives_data ?? [],
            'quality_activities_data' => $this->quality_activities_data ?? [],
            'operational_activities_data' => $this->operational_activities_data ?? [],
            'compliance_data' => $this->compliance_data ?? [],
            'non_conformity_data' => $this->non_conformity_data ?? [],
            'leadership_data' => $this->leadership_data ?? [],
            'duerp_data' => $this->duerp_data ?? [],
            'computed_metrics' => $this->resolveComputedMetrics(),
            'other_notes' => $this->other_notes,
            'conclusion' => $this->conclusion,
            'report_pdf_path' => $this->report_pdf_path,
            'report_docx_path' => $this->report_docx_path,
            'report_generated_at' => $this->report_generated_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'leader' => $this->whenLoaded('leader', function () {
                return [
                    'id' => $this->leader?->id,
                    'name' => $this->leader?->name,
                ];
            }),
            'closed_by_user' => $this->whenLoaded('closedBy', function () {
                return [
                    'id' => $this->closedBy?->id,
                    'name' => $this->closedBy?->name,
                ];
            }),
        ];
    }

    private function resolveComputedMetrics(): array
    {
        $precomputed = method_exists($this->resource, 'getAttribute')
            ? $this->resource->getAttribute('computed_metrics')
            : null;

        if (is_array($precomputed)) {
            return $precomputed;
        }

        return app(ProcessReviewMetricsService::class)->compute($this->resource);
    }
}
