<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
     * Get the borrowings where the user is the borrower.
     */
    public function borrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class, 'borrower_id');
    }

    /**
     * Get the borrowings approved by the user.
     */
    public function approvedBorrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class, 'approved_by');
    }

    /**
     * Get the usages by the user.
     */
    public function usages(): HasMany
    {
        return $this->hasMany(Usage::class, 'user_id');
    }

    /**
     * Get the audit trails created by the user.
     */
    public function auditTrails(): HasMany
    {
        return $this->hasMany(AuditTrail::class, 'user_id');
    }

    /**
     * Scope a query to only active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only admin users.
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope a query to only staff users.
     */
    public function scopeStaff($query)
    {
        return $query->where('role', 'staf');
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the user is staff.
     */
    public function isStaff(): bool
    {
        return $this->role === 'staf';
    }
}
