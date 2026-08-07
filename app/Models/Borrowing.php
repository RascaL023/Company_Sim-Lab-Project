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
        'item_id',
        'borrower_id',
        'approved_by',
        'quantity',
        'borrow_date',
        'expected_return_date',
        'actual_return_date',
        'condition_before',
        'condition_after',
        'status',
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
            'status' => 'string',
            'quantity' => 'decimal:2',
            'borrow_date' => 'datetime',
            'expected_return_date' => 'datetime',
            'actual_return_date' => 'datetime',
            'borrower_id' => 'integer',
            'approved_by' => 'integer',
        ];
    }

    /**
     * Get the item that is being borrowed.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the user that borrowed the item.
     */
    public function borrower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'borrower_id');
    }

    /**
     * Get the user that approved the borrowing.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope a query to only borrowed items.
     */
    public function scopeDipinjam($query)
    {
        return $query->where('status', 'dipinjam');
    }

    /**
     * Scope a query to only returned items.
     */
    public function scopeDikembalikan($query)
    {
        return $query->where('status', 'dikembalikan');
    }

    /**
     * Scope a query to only late items.
     */
    public function scopeTerlambat($query)
    {
        return $query->where('status', 'terlambat');
    }

    /**
     * Scope a query to only overdue items (based on expected_return_date).
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotNull('expected_return_date')
            ->where('expected_return_date', '<', now())
            ->where('actual_return_date', null);
    }

    /**
     * Scope a query to only borrowings this month.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('borrow_date', '=', now()->month)
            ->whereYear('borrow_date', '=', now()->year);
    }

    /**
     * Check if the borrowing is overdue.
     */
    public function isOverdue(): bool
    {
        return !is_null($this->expected_return_date) && 
               is_null($this->actual_return_date) && 
               $this->expected_return_date < now();
    }

    /**
     * Check if the borrowing is returned.
     */
    public function isReturned(): bool
    {
        return !is_null($this->actual_return_date);
    }
}