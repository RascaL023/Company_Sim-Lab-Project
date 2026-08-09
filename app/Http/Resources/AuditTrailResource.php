<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditTrailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $this->auditable_id,
            'action' => $this->action,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
