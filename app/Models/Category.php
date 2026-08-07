<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'type',
        'description',
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
        ];
    }

    /**
     * Get the items for the category.
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Scope a query to only alat categories.
     */
    public function scopeAlat($query)
    {
        return $query->where('type', 'alat');
    }

    /**
     * Scope a query to only bahan categories.
     */
    public function scopeBahan($query)
    {
        return $query->where('type', 'bahan');
    }
}