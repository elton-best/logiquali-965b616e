<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class EmployeeEvaluationResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'period' => $this->period,
            'year' => $this->year,
            'scores' => $this->scores,
            'total_score' => $this->total_score,
            'comments' => $this->comments,
            'm13_d3_traceability' => $this->m13_d3_traceability,
            'm13_d6_traceability' => $this->m13_d6_traceability,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'site' => new SiteResource($this->whenLoaded('site')),
            'user' => new UserResource($this->whenLoaded('user')),
            'evaluator' => new UserResource($this->whenLoaded('evaluator')),
        ];
    }
}
