<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemUnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item' => new ItemResource($this->whenLoaded('item')),
            'serial_number' => $this->serial_number,
            'asset_tag' => $this->asset_tag,
            'condition' => $this->condition,
            'location_id' => $this->location_id,
            'location' => new LocationResource($this->whenLoaded('location')),
            'purchase_date' => $this->purchase_date,
            'expiry_date' => $this->expiry_date,
            'last_calibration_date' => $this->last_calibration_date,
            'next_calibration_date' => $this->next_calibration_date,
            'notes' => $this->notes,
            'is_good' => $this->condition === 'baik',
            'needs_calibration' => $this->needsCalibration(),
            'is_expired' => $this->expiry_date?->isPast(),
        ];
    }
}
