<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BorrowingItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'borrowing_request_id',
        'item_id',
        'item_unit_id',
        'quantity',
        'condition_before',
        'condition_after',
        'is_damaged',
        'damage_notes',
        'borrow_date',
        'expected_return_date',
        'actual_return_date',
        'checked_by',
        'checked_at',
        'check_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'condition_before' => 'string',
            'condition_after' => 'string',
            'is_damaged' => 'boolean',
            'quantity' => 'decimal:2',
            'borrow_date' => 'datetime',
            'expected_return_date' => 'datetime',
            'actual_return_date' => 'datetime',
            'checked_by' => 'integer',
            'checked_at' => 'datetime',
        ];
    }

    /**
     * Get the borrowing request this item belongs to.
     */
    public function borrowingRequest(): BelongsTo
    {
        return $this->belongsTo(BorrowingRequest::class, 'borrowing_request_id');
    }

    /**
     * Get the item (catalog) for this borrowing item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the physical unit for this borrowing item (for alat).
     */
    public function itemUnit(): BelongsTo
    {
        return $this->belongsTo(ItemUnit::class, 'item_unit_id');
    }

    /**
     * Get the user who checked the item on return.
     */
    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    /**
     * Get the borrowing transaction for this item.
     */
    public function borrowing(): HasMany
    {
        return $this->hasMany(Borrowing::class, 'borrowing_item_id');
    }

    /**
     * Check if the item has been borrowed (checked out).
     */
    public function isBorrowed(): bool
    {
        return ! is_null($this->borrow_date);
    }

    /**
     * Check if the item has been returned.
     */
    public function isReturned(): bool
    {
        return ! is_null($this->actual_return_date);
    }

    /**
     * Check if the item is overdue.
     */
    public function isOverdue(): bool
    {
        return ! is_null($this->expected_return_date) &&
               is_null($this->actual_return_date) &&
               $this->expected_return_date < now();
    }

    /**
     * Check if the item was returned damaged.
     */
    public function wasReturnedDamaged(): bool
    {
        return $this->is_damaged;
    }
}
