<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class SatisfactionSurveyResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'type' => $this->type,
            'year' => $this->year,
            'period' => $this->period,
            'respondent_name' => $this->respondent_name,
            'respondent_email' => $this->respondent_email,
            'responses' => $this->responses,
            'total_score' => $this->total_score,
            'satisfaction_level' => $this->satisfaction_level,
            'recommendations' => $this->recommendations,
            'm13_d3_traceability' => $this->m13_d3_traceability,
            'm13_d4_traceability' => $this->m13_d4_traceability,
            'm13_d6_traceability' => $this->m13_d6_traceability,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'site' => new SiteResource($this->whenLoaded('site')),
        ];
    }
}
