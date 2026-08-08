<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'is_alat' => $this->type === 'alat',
            'is_bahan' => $this->type === 'bahan',
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
