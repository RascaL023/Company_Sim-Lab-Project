<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'item_id',
        'item_unit_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reference_type',
        'reference_id',
        'performed_by',
        'notes',
        'occurred_at',
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
            'quantity' => 'decimal:2',
            'quantity_before' => 'decimal:2',
            'quantity_after' => 'decimal:2',
            'reference_type' => 'string',
            'reference_id' => 'integer',
            'performed_by' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * Get the item (catalog) for this movement.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the physical unit for this movement.
     */
    public function itemUnit(): BelongsTo
    {
        return $this->belongsTo(ItemUnit::class, 'item_unit_id');
    }

    /**
     * Get the user who performed this movement.
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Get the reference model (polymorphic).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    /**
     * Scope a query to only incoming movements.
     */
    public function scopeIncoming($query)
    {
        return $query->whereIn('type', ['in_purchase', 'in_return', 'in_adjustment', 'transfer_in']);
    }

    /**
     * Scope a query to only outgoing movements.
     */
    public function scopeOutgoing($query)
    {
        return $query->whereIn('type', ['out_borrow', 'out_usage', 'out_disposal', 'out_adjustment', 'transfer_out']);
    }

    /**
     * Scope a query to only movements for a specific item.
     */
    public function scopeForItem($query, $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    /**
     * Scope a query to only movements of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to movements in a date range.
     */
    public function scopeBetweenDates($query, $start, $end)
    {
        return $query->whereBetween('occurred_at', [$start, $end]);
    }

    /**
     * Check if this is an incoming movement.
     */
    public function isIncoming(): bool
    {
        return in_array($this->type, ['in_purchase', 'in_return', 'in_adjustment', 'transfer_in']);
    }

    /**
     * Check if this is an outgoing movement.
     */
    public function isOutgoing(): bool
    {
        return in_array($this->type, ['out_borrow', 'out_usage', 'out_disposal', 'out_adjustment', 'transfer_out']);
    }
}
