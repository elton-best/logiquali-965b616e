<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class ContextResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'title' => $this->title,
            'year' => $this->year,
            'type' => $this->type,
            'category' => $this->category,
            'description' => $this->description,
            'impact' => $this->impact,
            'analysis' => $this->analysis,
            // SWOT fields
            'swot_strengths' => $this->swot_strengths,
            'swot_weaknesses' => $this->swot_weaknesses,
            'swot_opportunities' => $this->swot_opportunities,
            'swot_threats' => $this->swot_threats,
            // PESTEL fields
            'pestel_political' => $this->pestel_political,
            'pestel_economic' => $this->pestel_economic,
            'pestel_social' => $this->pestel_social,
            'pestel_technological' => $this->pestel_technological,
            'pestel_environmental' => $this->pestel_environmental,
            'pestel_legal' => $this->pestel_legal,
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

