<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Borrowing extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'borrowing_item_id',
        'borrower_id',
        'borrow_date',
        'expected_return_date',
        'actual_return_date',
        'condition_before',
        'condition_after',
        'is_damaged',
        'damage_notes',
        'checked_out_by',
        'checked_in_by',
        'checked_by',
        'checked_at',
        'check_notes',
        'status',
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
            'condition_before' => 'string',
            'condition_after' => 'string',
            'is_damaged' => 'boolean',
            'borrow_date' => 'datetime',
            'expected_return_date' => 'datetime',
            'actual_return_date' => 'datetime',
            'borrower_id' => 'integer',
            'checked_out_by' => 'integer',
            'checked_in_by' => 'integer',
            'checked_by' => 'integer',
            'checked_at' => 'datetime',
            'status' => 'string',
        ];
    }

    /**
     * Get the borrowing item this transaction belongs to.
     */
    public function borrowingItem(): BelongsTo
    {
        return $this->belongsTo(BorrowingItem::class, 'borrowing_item_id');
    }

    /**
     * Get the user who borrowed the item.
     */
    public function borrower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'borrower_id');
    }

    /**
     * Get the user who checked out the item.
     */
    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    /**
     * Get the user who checked in the item.
     */
    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    /**
     * Get the user who checked the item condition on return.
     */
    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    /**
     * Scope a query to only active borrowings.
     */
    public function scopeDipinjam($query)
    {
        return $query->where('status', 'dipinjam');
    }

    /**
     * Scope a query to only returned borrowings.
     */
    public function scopeDikembalikan($query)
    {
        return $query->where('status', 'dikembalikan');
    }

    /**
     * Scope a query to only overdue borrowings.
     */
    public function scopeTerlambat($query)
    {
        return $query->where('status', 'terlambat');
    }

    /**
     * Scope a query to only lost borrowings.
     */
    public function scopeHilang($query)
    {
        return $query->where('status', 'hilang');
    }

    /**
     * Check if the borrowing is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'dipinjam';
    }

    /**
     * Check if the borrowing is returned.
     */
    public function isReturned(): bool
    {
        return $this->status === 'dikembalikan';
    }

    /**
     * Check if the borrowing is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === 'terlambat' ||
               (! is_null($this->expected_return_date) &&
                is_null($this->actual_return_date) &&
                $this->expected_return_date < now());
    }
}
