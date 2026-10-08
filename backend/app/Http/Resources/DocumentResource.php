<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class DocumentResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'type' => $this->type,
            'code' => $this->code,
            'title' => $this->title,
            'version' => $this->version,
            'file_path' => $this->file_path,
            'template_path' => $this->template_path,
            'status' => $this->status,
            'needs_verification' => (bool) $this->needs_verification,
            'workflow_status' => $this->workflow_status,
            'rejection_reason' => $this->rejection_reason,
            'verifier_id' => $this->verifier_id,
            'approver_id' => $this->approver_id,
            'module_type' => $this->module_type,
            'module_id' => $this->module_id,
            'source_type' => $this->source_type,
            'nomenclature_template_id' => $this->nomenclature_template_id,
            'nomenclature_template_version' => $this->nomenclature_template_version,
            // Type documentaire depuis la configuration personnalisée de l'entreprise
            'type_configuration_name' => $this->whenLoaded('typeConfiguration', fn() => $this->typeConfiguration?->name),
            'type_configuration_abbreviation' => $this->whenLoaded('typeConfiguration', fn() => $this->typeConfiguration?->abbreviation),
            'source_module' => $this->source_module ?: data_get($this->metadata, 'source_context.module'),
            'source_submodule' => $this->source_submodule ?: data_get($this->metadata, 'source_context.submodule'),
            'source_section' => $this->source_section ?: data_get($this->metadata, 'source_context.section'),
            'inventory_link' => data_get($this->metadata, 'inventory_link'),
            'approved_at' => $this->approved_at?->toISOString(),
            'verified_at' => $this->verified_at?->toISOString(),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            // QHSE fields
            'document_number' => $this->document_number,
            'description' => $this->description,
            'keywords' => $this->keywords,
            'tags' => $this->tags,
            'metadata' => $this->metadata,
            'language' => $this->language,
            'effective_date' => $this->effective_date?->toISOString(),
            'review_due_date' => $this->review_due_date?->toISOString(),
            'retention_period_years' => $this->retention_period_years,
            'is_confidential' => $this->is_confidential,
            'confidentiality_level' => $this->confidentiality_level,
            'published_at' => $this->published_at?->toISOString(),
            'archived_at' => $this->archived_at?->toISOString(),
            'is_published' => $this->isPublished(),
            'is_archived' => $this->isArchived(),
            'is_review_overdue' => $this->isReviewOverdue(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'site' => new SiteResource($this->whenLoaded('site')),
            'process' => new ProcessResource($this->whenLoaded('process')),
            'processes' => ProcessResource::collection($this->whenLoaded('processes')),
            'author' => new UserResource($this->whenLoaded('author')),
            'approver' => new UserResource($this->whenLoaded('approver')),
            'verifier' => new UserResource($this->whenLoaded('verifier')),
            'category' => $this->whenLoaded('category', fn() => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'code' => $this->category->code,
                'level' => $this->category->level,
            ]),
            'workflow' => $this->whenLoaded('workflow', fn() => [
                'id' => $this->workflow->id,
                'name' => $this->workflow->name,
                'steps_count' => $this->workflow->steps_count,
            ]),
            'workflow_events' => $this->whenLoaded('workflowEvents', fn() =>
                $this->workflowEvents->map(fn($event) => [
                    'id' => $event->id,
                    'event_type' => $event->event_type,
                    'from_status' => $event->from_status,
                    'to_status' => $event->to_status,
                    'comment' => $event->comment,
                    'chain_index' => $event->chain_index,
                    'event_hash' => $event->event_hash,
                    'previous_event_hash' => $event->previous_event_hash,
                    'event_signature' => $event->event_signature,
                    'occurred_at' => $event->occurred_at?->toISOString(),
                    'sealed_at' => $event->sealed_at?->toISOString(),
                    'actor' => $event->actor ? [
                        'id' => $event->actor->id,
                        'name' => $event->actor->name,
                        'email' => $event->actor->email,
                    ] : null,
                    'metadata' => $event->metadata,
                ])
            ),
            'current_version' => $this->whenLoaded('currentVersion', fn() => [
                'id' => $this->currentVersion->id,
                'version_number' => $this->currentVersion->version_number,
                'file_size_human' => $this->currentVersion->file_size_human,
                'file_mime_type' => $this->currentVersion->file_mime_type,
                'file_original_name' => $this->currentVersion->file_original_name,
                'created_at' => $this->currentVersion->created_at?->toISOString(),
            ]),
            'versions' => $this->whenLoaded('versions', fn() => 
                $this->versions->map(fn($v) => [
                    'id' => $v->id,
                    'version_number' => $v->version_number,
                    'is_current' => $v->is_current,
                    'change_summary' => $v->change_summary,
                    'created_at' => $v->created_at?->toISOString(),
                ])
            ),
        ];
    }
}
