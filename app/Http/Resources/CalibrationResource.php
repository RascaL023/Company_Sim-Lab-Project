<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalibrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_unit' => new ItemUnitResource($this->whenLoaded('itemUnit')),
            'calibration_date' => $this->calibration_date,
            'next_calibration_date' => $this->next_calibration_date,
            'calibrated_by' => $this->calibrated_by,
            'certificate_number' => $this->certificate_number,
            'result' => $this->result,
            'passes_calibration' => $this->result === 'lulus',
            'notes' => $this->notes,
            'recorded_by' => new UserResource($this->whenLoaded('recordedBy')),
        ];
    }
}
