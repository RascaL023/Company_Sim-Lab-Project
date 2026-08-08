<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BorrowingRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_number' => $this->request_number,
            'status' => $this->status,
            'purpose' => $this->purpose,
            'rejection_reason' => $this->rejection_reason,
            'requested_by' => new UserResource($this->whenLoaded('requestedBy')),
            'approved_by' => new UserResource($this->whenLoaded('approvedBy')),
            'items' => BorrowingItemResource::collection($this->whenLoaded('items')),
            'requested_at' => $this->requested_at,
            'approved_at' => $this->approved_at,
            'rejected_at' => $this->rejected_at,
            'notes' => $this->notes,
        ];
    }
}
