<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attachable_type' => $this->attachable_type,
            'attachable_id' => $this->attachable_id,
            'type' => $this->type,
            'file_path' => $this->file_path,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'size_human' => $this->file_size ? round($this->file_size / 1024, 2).' KB' : null,
            'disk' => $this->disk,
            'description' => $this->description,
            'uploaded_by' => new UserResource($this->whenLoaded('uploader')),
            'created_at' => $this->created_at,
        ];
    }
}
