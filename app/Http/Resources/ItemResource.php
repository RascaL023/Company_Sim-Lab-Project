<?php

namespace App\Http\Resources;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Item $resource
 */
class ItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'unit' => $this->unit,
            'stock_quantity' => $this->stock_quantity,
            'minimum_stock' => $this->minimum_stock,
            'location' => $this->location,
            'manufacturer' => $this->manufacturer,
            'description' => $this->description,
            'is_alat' => $this->isAlat(),
            'is_bahan' => $this->isBahan(),
            'is_low_stock' => $this->isLowStock(),
            'needs_calibration' => $this->needsCalibration(),
            'is_expired' => $this->isExpired(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,

            // Relationships (eager loaded)
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'creator' => UserResource::make($this->whenLoaded('creator')),

            // Counts for collections (optional, can be heavy)
            'calibrations_count' => $this->whenCounted('calibrations'),
            'maintenances_count' => $this->whenCounted('maintenances'),
            'borrowings_count' => $this->whenCounted('borrowings'),
            'usages_count' => $this->whenCounted('usages'),
        ];
    }
}
