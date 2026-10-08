<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;
use Illuminate\Http\Request;

class ProcessResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'title' => $this->title,
            'code' => $this->code,
            'abbreviation' => $this->abbreviation,
            'category' => $this->category,
            'type' => $this->type,
            'purpose' => $this->purpose,
            'finalite' => $this->finalite,
            'observation' => $this->observation,
            'level' => $this->level,
            'order' => $this->order,
            'version' => $this->version,
            'status' => $this->status,
            'is_validated' => $this->is_validated,
            'validated_at' => $this->validated_at?->toISOString(),
            'pilot_id' => $this->pilot_id,
            'copilot_id' => $this->copilot_id,
            'copilot_ids' => $this->relationLoaded('copilots')
                ? $this->copilots->pluck('id')->values()->all()
                : [],
            'parent_process_id' => $this->parent_process_id,
            'site_id' => $this->site_id,
            // Aspects QHSE
            'aspect_qualite' => $this->aspect_qualite ?? false,
            'aspect_environnement' => $this->aspect_environnement ?? false,
            'aspect_sante_securite' => $this->aspect_sante_securite ?? false,
            // Arrays
            'acteurs' => $this->acteurs ?? [],
            'ressources' => $this->ressources ?? [],
            'methodes' => $this->methodes ?? [],
            'interfaces' => $this->interfaces ?? [],
            'normes_iso' => $this->normes_iso ?? [],
            // Dates
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'site' => new SiteResource($this->whenLoaded('site')),
            'pilot' => new UserResource($this->whenLoaded('pilot')),
            'copilot' => new UserResource($this->whenLoaded('copilot')),
            'copilots' => UserResource::collection($this->whenLoaded('copilots')),
            'validator' => new UserResource($this->whenLoaded('validator')),
            'parent_process' => new ProcessResource($this->whenLoaded('parentProcess')),
            'child_processes' => ProcessResource::collection($this->whenLoaded('childProcesses')),
            // Process-specific relations
            'sequences' => $this->when($this->relationLoaded('sequences'), function () {
                return $this->sequences->map(function ($sequence) {
                    return [
                        'id' => $sequence->id,
                        'sequence_order' => $sequence->sequence_order,
                        'input_description' => $sequence->input_description,
                        'activity_description' => $sequence->activity_description,
                        'sub_activities' => $sequence->sub_activities ?? [],
                        'output_description' => $sequence->output_description,
                        'supplier_processes' => $sequence->supplier_processes ?? [],
                        'client_processes' => $sequence->client_processes ?? [],
                        'responsible_user_id' => $sequence->responsible_user_id,
                        'duration_minutes' => $sequence->duration_minutes,
                        'responsible' => $sequence->relationLoaded('responsibleUser') ? [
                            'id' => $sequence->responsibleUser?->id,
                            'name' => $sequence->responsibleUser?->name,
                        ] : null,
                    ];
                });
            }),
            'objectives' => $this->when($this->relationLoaded('processObjectives'), function () {
                return $this->processObjectives->map(function ($objective) {
                    return [
                        'id' => $objective->id,
                        'title' => $objective->title,
                        'strategic_axis' => $objective->strategic_axis,
                        'strategic_axes' => $objective->strategic_axes ?? [],
                        'description' => $objective->description,
                        'calculation_mode' => $objective->calculation_mode,
                        'target_value' => $objective->target_value,
                        'target_date' => $objective->target_date,
                        'measurement_frequency' => $objective->measurement_frequency,
                        'status' => $objective->status,
                        'achievement_percentage' => $objective->achievement_percentage ?? 0,
                        'period_realizations' => $objective->period_realizations ?? [],
                        'notes' => $objective->notes,
                        'action_plan' => $objective->action_plan,
                        'planned_actions' => $objective->planned_actions ?? [],
                        'responsibles' => $objective->responsibles,
                        'special_resources' => $objective->special_resources,
                        'indicator_id' => $objective->indicator_id,
                        'indicator_name' => $objective->indicator_name,
                        'indicator' => $objective->relationLoaded('indicator') ? [
                            'id' => $objective->indicator?->id,
                            'name' => $objective->indicator?->name,
                            'code' => $objective->indicator?->code,
                            'unit' => $objective->indicator?->unit,
                            'formula' => $objective->indicator?->formula,
                        ] : null,
                    ];
                });
            }),
            'versions' => $this->when($this->relationLoaded('versions'), function () {
                return $this->versions->map(function ($version) {
                    return [
                        'id' => $version->id,
                        'version_number' => $version->version_number,
                        'changes_description' => $version->changes_description,
                        'status' => $version->status,
                        'version_date' => $version->version_date,
                        'is_current' => $version->is_current,
                        'verified_at' => $version->verified_at,
                        'approved_at' => $version->approved_at,
                        'author' => $version->relationLoaded('author') ? [
                            'id' => $version->author?->id,
                            'name' => $version->author?->name,
                        ] : null,
                        'verifier' => $version->relationLoaded('verifier') ? [
                            'id' => $version->verifier?->id,
                            'name' => $version->verifier?->name,
                        ] : null,
                        'approver' => $version->relationLoaded('approver') ? [
                            'id' => $version->approver?->id,
                            'name' => $version->approver?->name,
                        ] : null,
                    ];
                });
            }),
            'indicators' => $this->when($this->relationLoaded('indicators'), function () {
                return $this->indicators->map(function ($indicator) {
                    return [
                        'id' => $indicator->id,
                        'code' => $indicator->code,
                        'name' => $indicator->name,
                        'description' => $indicator->description,
                        'type' => $indicator->type,
                        'category' => $indicator->category,
                        'unit' => $indicator->unit,
                        'measurement_frequency' => $indicator->measurement_frequency,
                        'target_value' => $indicator->target_value,
                        'min_threshold' => $indicator->min_threshold,
                        'max_threshold' => $indicator->max_threshold,
                        'alert_threshold' => $indicator->alert_threshold,
                        'formula' => $indicator->formula,
                        'status' => $indicator->status,
                    ];
                });
            }),
            'risks_opportunities' => $this->when($this->relationLoaded('risksOpportunities'), function () {
                return $this->risksOpportunities->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'type' => $item->type,
                        'code' => $item->code,
                        'title' => $item->title,
                        'description' => $item->description,
                        'normes_iso' => $item->normes_iso ?? [],
                        'niveau' => $item->niveau,
                        'status' => $item->status,
                    ];
                });
            }),
            'current_version' => $this->when($this->relationLoaded('currentVersion'), function () {
                $cv = $this->currentVersion;
                return $cv ? [
                    'id' => $cv->id,
                    'version_number' => $cv->version_number,
                    'status' => $cv->status,
                ] : null;
            }),
            // Legacy relations for compatibility
            'activities' => ActivityResource::collection($this->whenLoaded('activities')),
            'risks' => RiskResource::collection($this->whenLoaded('risks')),
            'opportunities' => OpportunityResource::collection($this->whenLoaded('opportunities')),
        ];
    }
}
