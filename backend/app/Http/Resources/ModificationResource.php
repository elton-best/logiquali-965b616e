<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;

class ModificationResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'number' => $this->number,
            'date' => $this->date?->toISOString(),
            'initiator_id' => $this->initiator_id,
            'object' => $this->object,
            'description' => $this->description,
            'scope' => $this->scope,
            'objectives' => $this->objectives,
            'consequences' => $this->consequences,
            'required_resources' => $this->required_resources,
            'document_ids' => $this->document_ids ?? [],
            'result_owner_id' => $this->result_owner_id,
            'monitoring_owner_id' => $this->monitoring_owner_id,
            'validated_at' => $this->validated_at?->toISOString(),
            'status' => $this->status,
            'workflow_status' => $this->workflow_status,
            'approved_version' => $this->approved_version,
            'approved_at' => $this->approved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'site' => new SiteResource($this->whenLoaded('site')),
            'responsible' => new UserResource($this->whenLoaded('responsible')),
            'validator' => new UserResource($this->whenLoaded('validator')),
            'initiator' => new UserResource($this->whenLoaded('initiator')),
            'result_owner' => new UserResource($this->whenLoaded('resultOwner')),
            'monitoring_owner' => new UserResource($this->whenLoaded('monitoringOwner')),
        ];
    }
}
