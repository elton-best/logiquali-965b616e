<?php

namespace App\Http\Resources\Base;

use Illuminate\Http\Resources\Json\ResourceCollection;

class JsonApiCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection,
        ];
    }

    public function with($request)
    {
        $pagination = $this->resource->toArray();
        
        return [
            'jsonapi' => [
                'version' => '1.0',
            ],
            'links' => [
                'self' => $request->url(),
                'first' => $pagination['first_page_url'] ?? null,
                'last' => $pagination['last_page_url'] ?? null,
                'prev' => $pagination['prev_page_url'] ?? null,
                'next' => $pagination['next_page_url'] ?? null,
            ],
            'meta' => [
                'current_page' => $pagination['current_page'] ?? 1,
                'from' => $pagination['from'] ?? null,
                'last_page' => $pagination['last_page'] ?? 1,
                'per_page' => $pagination['per_page'] ?? 20,
                'to' => $pagination['to'] ?? null,
                'total' => $pagination['total'] ?? 0,
            ],
        ];
    }
}
