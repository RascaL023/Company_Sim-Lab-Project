<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BorrowingRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'request_number',
        'requested_by',
        'approved_by',
        'status',
        'purpose',
        'rejection_reason',
        'requested_at',
        'approved_at',
        'rejected_at',
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
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'requested_by' => 'integer',
            'approved_by' => 'integer',
        ];
    }

    /**
     * Get the user who requested the borrowing.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who requested the borrowing.
     */
    public function requestedBy(): BelongsTo
    {
        return $this->requester();
    }

    /**
     * Get the user who approved/rejected the request.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who approved/rejected the request.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->approver();
    }

    /**
     * Get the borrowing items in this request.
     */
    public function items(): HasMany
    {
        return $this->hasMany(BorrowingItem::class, 'borrowing_request_id');
    }

    /**
     * Scope a query to only pending requests.
     */
    public function scopeDiajukan($query)
    {
        return $query->where('status', 'diajukan');
    }

    /**
     * Scope a query to only approved requests.
     */
    public function scopeDisetujui($query)
    {
        return $query->where('status', 'disetujui');
    }

    /**
     * Scope a query to only rejected requests.
     */
    public function scopeDitolak($query)
    {
        return $query->where('status', 'ditolak');
    }

    /**
     * Scope a query to only requests being processed.
     */
    public function scopeDiproses($query)
    {
        return $query->where('status', 'diproses');
    }

    /**
     * Scope a query to only completed requests.
     */
    public function scopeSelesai($query)
    {
        return $query->where('status', 'selesai');
    }

    /**
     * Scope a query to only cancelled requests.
     */
    public function scopeBatal($query)
    {
        return $query->where('status', 'batal');
    }

    /**
     * Check if the request is pending approval.
     */
    public function isPending(): bool
    {
        return $this->status === 'diajukan';
    }

    /**
     * Check if the request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'disetujui';
    }

    /**
     * Check if the request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'ditolak';
    }

    /**
     * Check if the request is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'selesai';
    }
}
