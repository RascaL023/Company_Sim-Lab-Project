<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'type',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'disk',
        'description',
        'uploaded_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => 'string',
            'file_size' => 'integer',
            'uploaded_by' => 'integer',
        ];
    }

    /**
     * Get the attachable model (polymorphic).
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded this attachment.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scope a query to attachments of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the full URL to the file.
     */
    public function getUrl(): string
    {
        return \Storage::disk($this->disk)->url($this->file_path);
    }
}
