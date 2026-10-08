<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class ApplicationScopeResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        $metadata = is_array($this->metadata) ? $this->metadata : [];
        $normExclusions = $this->norm_exclusions;
        $normExclusionsJustifications = $this->norm_exclusions_justifications;

        if (empty($normExclusions) && isset($metadata['norm_exclusions'])) {
            $normExclusions = $metadata['norm_exclusions'];
        }

        if (empty($normExclusionsJustifications) && isset($metadata['norm_exclusions_justifications'])) {
            $normExclusionsJustifications = $metadata['norm_exclusions_justifications'];
        }

        return [
            'version' => $this->version,
            'is_current' => $this->is_current,
            'objective' => $this->objective,
            'scope' => $this->scope,
            'document_objective' => $this->document_objective,
            'scope_definition' => $this->scope_definition,
            'referenced_documents' => $this->referenced_documents,
            'processes' => $this->processes,
            'included_processes' => $this->included_processes,
            'products_services' => $this->products_services,
            'organizational_units' => $this->organizational_units,
            'locations' => $this->locations,
            'exclusions' => $this->exclusions,
            'scope_exclusions' => $this->scope_exclusions,
            'exclusions_justification' => $this->exclusions_justification,
            'iso_exclusions' => $this->iso_exclusions,
            'iso_exclusions_justification' => $this->iso_exclusions_justification,
            'norm_exclusions' => $normExclusions,
            'norm_exclusions_justifications' => $normExclusionsJustifications,
            'applicable_norms' => $this->applicable_norms,
            'document_generated' => $this->document_generated,
            'document_path' => $this->document_path,
            'generated_at' => $this->generated_at?->toISOString(),
            'metadata' => $metadata,
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
