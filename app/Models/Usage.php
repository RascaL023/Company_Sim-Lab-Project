<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Usage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'item_id',
        'user_id',
        'quantity_used',
        'usage_date',
        'purpose',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_used' => 'decimal:2',
            'usage_date' => 'datetime',
            'item_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    /**
     * Get the item that was used.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the user that used the item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only usages this month.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('usage_date', '=', now()->month)
            ->whereYear('usage_date', '=', now()->year);
    }

    /**
     * Scope a query to only usages by a specific item.
     */
    public function scopeByItem($query, $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    /**
     * Scope a query to only usages by a specific user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}