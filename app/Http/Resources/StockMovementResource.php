<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item' => new ItemResource($this->whenLoaded('item')),
            'item_unit' => new ItemUnitResource($this->whenLoaded('itemUnit')),
            'type' => $this->type,
            'quantity' => $this->quantity,
            'quantity_before' => $this->quantity_before,
            'quantity_after' => $this->quantity_after,
            'performed_by' => new UserResource($this->whenLoaded('performedBy')),
            'notes' => $this->notes,
            'occurred_at' => $this->occurred_at,
        ];
    }
}
