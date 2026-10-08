<?php

namespace App\Http\Resources;

use App\Http\Resources\Base\JsonApiResource;
use Illuminate\Http\Request;
use App\Http\Resources\ActionResource;

class ComplaintResource extends JsonApiResource
{
    protected function getAttributes(): array
    {
        return [
            'ref' => $this->ref,
            'title' => $this->title,
            'description' => $this->description,
            'site_id' => $this->site_id,
            'user_id' => $this->user_id,
            'category' => $this->category,
            'priority' => $this->priority,
            'wants_mail' => $this->wants_mail,
            'wants_email_response' => $this->wants_email_response,
            'expected_solution' => $this->expected_solution,
            'recommandations' => $this->recommandations,
            'analysis' => $this->analysis,
            'immediate_response' => $this->immediate_response,
            'status' => $this->status,
            'assigned_to' => $this->assigned_to,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'customer_address' => $this->customer_address,
            'client_name' => $this->client_name,
            'client_email' => $this->client_email,
            'client_phone' => $this->client_phone,
            'client_company' => $this->client_company,
            'received_date' => $this->received_date?->toISOString(),
            'due_date' => $this->due_date?->toISOString(),
            'response_date' => $this->response_date?->toISOString(),
            'closed_date' => $this->closed_date?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getRelationships(): array
    {
        return [
            'user' => new UserResource($this->whenLoaded('user')),
            'site' => new SiteResource($this->whenLoaded('site')),
            'assigned_user' => new UserResource($this->whenLoaded('assignedUser')),
            'actions' => ActionResource::collection($this->whenLoaded('actions')),
        ];
    }
}
