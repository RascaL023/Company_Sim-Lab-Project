<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetDisposalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'item_unit_id' => $this->item_unit_id,
            'item' => new ItemResource($this->whenLoaded('item')),
            'item_unit' => new ItemUnitResource($this->whenLoaded('itemUnit')),
            'reason' => $this->reason,
            'notes' => $this->notes,
            'status' => $this->status,
            'proposed_by' => new UserResource($this->whenLoaded('proposer')),
            'proposed_at' => $this->proposed_at,
            'reviewed_by' => new UserResource($this->whenLoaded('reviewer')),
            'reviewed_at' => $this->reviewed_at,
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
