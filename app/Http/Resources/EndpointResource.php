<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EndpointResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "base_api" => $this->base_api->getBaseUrl(),
            "endpoint" => $this->endpoint,
            "method" => $this->method,
            "description" => $this->description,
            "status" => $this->status,
            "headers" => $this->headers,
            "payload" => $this->payload,
            "parameters" => $this->parameters,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
