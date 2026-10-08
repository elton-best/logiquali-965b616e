<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class JobDescriptionResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'job_title' => $this->job_title,
            'replacement_job_title' => $this->replacement_job_title,
            'department' => $this->department,
            'reports_to_id' => $this->reports_to_id,
            'mission' => $this->mission,
            'activities' => $this->activities,
            'main_activities' => $this->main_activities,
            'secondary_activities' => $this->secondary_activities,
            'internal_relations' => $this->internal_relations,
            'external_relations' => $this->external_relations,
            'work_location' => $this->work_location,
            'work_schedule' => $this->work_schedule,
            'travel_required' => $this->travel_required,
            'physical_requirements' => $this->physical_requirements,
            'functional_relations' => $this->functional_relations,
            'hierarchical_superior' => $this->hierarchical_superior,
            'work_conditions' => $this->work_conditions,
            'work_environment' => $this->work_environment,
            'required_education' => $this->required_education,
            'required_experience' => $this->required_experience,
            'certifications_required' => $this->certifications_required,
            'required_level' => $this->required_level,
            'current_level' => $this->current_level,
            'required_skills' => $this->required_skills,
            'professional_qualities' => $this->professional_qualities,
            'employee_signature_data' => $this->employee_signature_data,
            'employee_signed_at' => $this->employee_signed_at?->toISOString(),
            'signature' => $this->employee_signature_data,
            'site_id' => $this->site_id,
            'user_id' => $this->user_id,
            'enterprise_id' => $this->enterprise_id,
            'last_updated' => $this->last_updated?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'site' => new SiteResource($this->whenLoaded('site')),
            'user' => new UserResource($this->whenLoaded('user')),
            'history' => $this->whenLoaded('history', fn() => $this->history->map(fn($item) => [
                'id' => $item->id,
                'field_key' => $item->field_key,
                'old_value' => $item->old_value,
                'new_value' => $item->new_value,
                'change_source' => $item->change_source,
                'changed_at' => $item->changed_at?->toISOString(),
                'changed_by' => $item->changed_by,
            ])),
        ];
    }
}
