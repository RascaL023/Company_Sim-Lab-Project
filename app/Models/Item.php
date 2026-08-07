<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'code',
        'name',
        'type',
        'unit',
        'stock_quantity',
        'minimum_stock',
        'location',
        'condition_status',
        'manufacturer',
        'serial_number',
        'purchase_date',
        'expiry_date',
        'next_calibration_date',
        'description',
        'created_by',
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
            'condition_status' => 'string',
            'stock_quantity' => 'decimal:2',
            'minimum_stock' => 'decimal:2',
            'purchase_date' => 'date',
            'expiry_date' => 'date',
            'next_calibration_date' => 'date',
            'created_by' => 'integer',
        ];
    }

    /**
     * Get the category that owns the item.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the user that created the item.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the calibrations for the item.
     */
    public function calibrations(): HasMany
    {
        return $this->hasMany(ItemCalibration::class);
    }

    /**
     * Get the maintenances for the item.
     */
    public function maintenances(): HasMany
    {
        return $this->hasMany(ItemMaintenance::class);
    }

    /**
     * Get the borrowings for the item.
     */
    public function borrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class);
    }

    /**
     * Get the usages for the item.
     */
    public function usages(): HasMany
    {
        return $this->hasMany(Usage::class);
    }

    /**
     * Get the audit trails for the item.
     */
    public function auditTrails(): MorphMany
    {
        return $this->morphMany(AuditTrail::class, 'auditable');
    }

    /**
     * Scope a query to only alat items.
     */
    public function scopeAlat($query)
    {
        return $query->where('type', 'alat');
    }

    /**
     * Scope a query to only bahan items.
     */
    public function scopeBahan($query)
    {
        return $query->where('type', 'bahan');
    }

    /**
     * Scope a query to only items in good condition.
     */
    public function scopeBaik($query)
    {
        return $query->where('condition_status', 'baik');
    }

    /**
     * Scope a query to only damaged items.
     */
    public function scopeRusak($query)
    {
        return $query->where('condition_status', 'rusak');
    }

    /**
     * Scope a query to only items under maintenance.
     */
    public function scopeMaintenance($query)
    {
        return $query->where('condition_status', 'maintenance');
    }

    /**
     * Scope a query to only expired items.
     */
    public function scopeKadaluarsa($query)
    {
        return $query->where('condition_status', 'kadaluarsa');
    }

    /**
     * Scope a query to only items with low stock.
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<', 'minimum_stock');
    }

    /**
     * Scope a query to only items needing calibration.
     */
    public function scopeNeedsCalibration($query)
    {
        return $query->whereNotNull('next_calibration_date')
            ->where('next_calibration_date', '<', now()->toDateString());
    }

    /**
     * Scope a query to only expired items (based on expiry_date).
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now()->toDateString());
    }

    /**
     * Check if the item is alat.
     */
    public function isAlat(): bool
    {
        return $this->type === 'alat';
    }

    /**
     * Check if the item is bahan.
     */
    public function isBahan(): bool
    {
        return $this->type === 'bahan';
    }

    /**
     * Check if the item is low stock.
     */
    public function isLowStock(): bool
    {
        return $this->stock_quantity < $this->minimum_stock;
    }

    /**
     * Check if the item needs calibration.
     */
    public function needsCalibration(): bool
    {
        return !is_null($this->next_calibration_date) && 
               $this->next_calibration_date < now()->toDateString();
    }

    /**
     * Check if the item is expired.
     */
    public function isExpired(): bool
    {
        return !is_null($this->expiry_date) && 
               $this->expiry_date < now()->toDateString();
    }
}