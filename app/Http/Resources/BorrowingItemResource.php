<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BorrowingItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item' => new ItemResource($this->whenLoaded('item')),
            'item_unit' => new ItemUnitResource($this->whenLoaded('itemUnit')),
            'quantity' => $this->quantity,
            'condition_before' => $this->condition_before,
            'condition_after' => $this->condition_after,
            'is_damaged' => $this->is_damaged,
            'damage_notes' => $this->damage_notes,
            'borrow_date' => $this->borrow_date,
            'expected_return_date' => $this->expected_return_date,
            'actual_return_date' => $this->actual_return_date,
            'checked_by' => new UserResource($this->whenLoaded('checkedBy')),
            'checked_at' => $this->checked_at,
            'check_notes' => $this->check_notes,
        ];
    }
}
