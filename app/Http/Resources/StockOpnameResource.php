<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockOpnameResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'notes' => $this->notes,
            'performed_by' => new UserResource($this->whenLoaded('performer')),
            'movements' => StockMovementResource::collection($this->whenLoaded('movements')),
            'summary' => $this->when(isset($this->summary), $this->summary),
            'created_at' => $this->created_at,
        ];
    }
}
