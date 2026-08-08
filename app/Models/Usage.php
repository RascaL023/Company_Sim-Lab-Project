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
        'item_unit_id',
        'user_id',
        'verified_by',
        'quantity_used',
        'quantity_before',
        'quantity_after',
        'usage_date',
        'status',
        'rejection_reason',
        'verified_at',
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
            'quantity_before' => 'decimal:2',
            'quantity_after' => 'decimal:2',
            'usage_date' => 'datetime',
            'status' => 'string',
            'verified_at' => 'datetime',
            'item_id' => 'integer',
            'item_unit_id' => 'integer',
            'user_id' => 'integer',
            'verified_by' => 'integer',
        ];
    }

    /**
     * Get the item (catalog) that was used.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the physical unit that was used (for alat with tracking).
     */
    public function itemUnit(): BelongsTo
    {
        return $this->belongsTo(ItemUnit::class, 'item_unit_id');
    }

    /**
     * Get the user who used the item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who verified the usage.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope a query to only recorded usages.
     */
    public function scopeDicatat($query)
    {
        return $query->where('status', 'dicatat');
    }

    /**
     * Scope a query to only verified usages.
     */
    public function scopeDiverifikasi($query)
    {
        return $query->where('status', 'diverifikasi');
    }

    /**
     * Scope a query to only rejected usages.
     */
    public function scopeDitolak($query)
    {
        return $query->where('status', 'ditolak');
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

    /**
     * Check if the usage is verified.
     */
    public function isVerified(): bool
    {
        return $this->status === 'diverifikasi';
    }

    /**
     * Check if the usage is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'ditolak';
    }

    /**
     * Check if the usage is pending verification.
     */
    public function isPendingVerification(): bool
    {
        return $this->status === 'dicatat';
    }
}
