<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiSettingResource extends JsonResource
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
            "api_name" => $this->api_name,
            "globalSettings" => $this->globalSettings,
            "authentication" => $this->authentication,
            "security" => $this->security,
            "logging" => $this->logging,
            "performance" => $this->performance,
            "versionControl" => $this->versionControl,
            "errorHandling" => $this->errorHandling,
            "updated_at" => $this->updated_at,
            "created_at" => $this->created_at,
        ];
    }
}
