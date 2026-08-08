<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item' => new ItemResource($this->whenLoaded('item')),
            'item_unit' => new ItemUnitResource($this->whenLoaded('itemUnit')),
            'user' => new UserResource($this->whenLoaded('user')),
            'verified_by' => new UserResource($this->whenLoaded('verifiedBy')),
            'quantity_used' => $this->quantity_used,
            'quantity_before' => $this->quantity_before,
            'quantity_after' => $this->quantity_after,
            'purpose' => $this->purpose,
            'status' => $this->status,
            'verified_at' => $this->verified_at,
            'rejection_reason' => $this->rejection_reason,
        ];
    }
}
