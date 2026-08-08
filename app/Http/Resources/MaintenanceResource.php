<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_unit' => new ItemUnitResource($this->whenLoaded('itemUnit')),
            'maintenance_date' => $this->maintenance_date,
            'description' => $this->description,
            'performed_by' => $this->performed_by,
            'cost' => $this->cost,
            'status' => $this->status,
            'is_completed' => $this->status === 'selesai',
            'notes' => $this->notes,
            'recorded_by' => new UserResource($this->whenLoaded('recordedBy')),
        ];
    }
}
