<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'string',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the items created by the user.
     */
    public function createdItems(): HasMany
    {
        return $this->hasMany(Item::class, 'created_by');
    }

    /**
     * Get the item units created by the user.
     */
    public function createdUnits(): HasMany
    {
        return $this->hasMany(ItemUnit::class, 'created_by');
    }

    /**
     * Get the calibrations recorded by the user.
     */
    public function recordedCalibrations(): HasMany
    {
        return $this->hasMany(ItemCalibration::class, 'recorded_by');
    }

    /**
     * Get the maintenances recorded by the user.
     */
    public function recordedMaintenances(): HasMany
    {
        return $this->hasMany(ItemMaintenance::class, 'recorded_by');
    }

    /**
     * Get the borrowing requests made by the user.
     */
    public function borrowingRequests(): HasMany
    {
        return $this->hasMany(BorrowingRequest::class, 'requested_by');
    }

    /**
     * Get the borrowing requests approved by the user.
     */
    public function approvedBorrowingRequests(): HasMany
    {
        return $this->hasMany(BorrowingRequest::class, 'approved_by');
    }

    /**
     * Get the borrowing items checked by the user.
     */
    public function checkedBorrowingItems(): HasMany
    {
        return $this->hasMany(BorrowingItem::class, 'checked_by');
    }

    /**
     * Get the borrowing items checked out by the user.
     */
    public function checkedOutBorrowingItems(): HasMany
    {
        return $this->hasMany(BorrowingItem::class, 'checked_out_by');
    }

    /**
     * Get the borrowing items checked in by the user.
     */
    public function checkedInBorrowingItems(): HasMany
    {
        return $this->hasMany(BorrowingItem::class, 'checked_in_by');
    }

    /**
     * Get the usages by the user.
     */
    public function usages(): HasMany
    {
        return $this->hasMany(Usage::class, 'user_id');
    }

    /**
     * Get the usages verified by the user.
     */
    public function verifiedUsages(): HasMany
    {
        return $this->hasMany(Usage::class, 'verified_by');
    }

    /**
     * Get the stock movements performed by the user.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'performed_by');
    }

    /**
     * Get the audit trails created by the user.
     */
    public function auditTrails(): HasMany
    {
        return $this->hasMany(AuditTrail::class, 'user_id');
    }

    /**
     * Get the attachments uploaded by the user.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'uploaded_by');
    }

    /**
     * Scope a query to only active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only admin sistem users.
     */
    public function scopeAdminsSistem($query)
    {
        return $query->where('role', 'admin_sistem');
    }

    /**
     * Scope a query to only laboran users.
     */
    public function scopeLaboran($query)
    {
        return $query->where('role', 'laboran');
    }

    /**
     * Scope a query to only kepala lab users.
     */
    public function scopeKepalaLab($query)
    {
        return $query->where('role', 'kepala_lab');
    }

    /**
     * Scope a query to only peminjam users.
     */
    public function scopePeminjam($query)
    {
        return $query->where('role', 'peminjam');
    }

    public function isAdminSistem(): bool
    {
        return $this->role === 'admin_sistem';
    }

    public function isLaboran(): bool
    {
        return $this->role === 'laboran';
    }

    public function isKepalaLab(): bool
    {
        return $this->role === 'kepala_lab';
    }

    public function isPeminjam(): bool
    {
        return $this->role === 'peminjam';
    }

    /**
     * Roles that may view all lab operational data (not limited to own records).
     */
    public function canViewAllLabRecords(): bool
    {
        return $this->isLaboran() || $this->isKepalaLab() || $this->isAdminSistem();
    }
}
