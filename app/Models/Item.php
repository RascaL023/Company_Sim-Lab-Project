<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
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
        'unit',
        'stock_quantity',
        'minimum_stock',
        'location',
        'manufacturer',
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
            'stock_quantity' => 'decimal:2',
            'minimum_stock' => 'decimal:2',
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
     * Get the physical units for this item.
     */
    public function units(): HasMany
    {
        return $this->hasMany(ItemUnit::class);
    }

    /**
     * Get the calibrations for this item (through units).
     */
    public function calibrations(): HasManyThrough
    {
        return $this->hasManyThrough(ItemCalibration::class, ItemUnit::class, 'item_id', 'item_unit_id');
    }

    /**
     * Get the maintenances for this item (through units).
     */
    public function maintenances(): HasManyThrough
    {
        return $this->hasManyThrough(ItemMaintenance::class, ItemUnit::class, 'item_id', 'item_unit_id');
    }

    /**
     * Get the borrowings for this item.
     */
    public function borrowings(): HasMany
    {
        return $this->hasMany(BorrowingItem::class);
    }

    /**
     * Get the usages for this item.
     */
    public function usages(): HasMany
    {
        return $this->hasMany(Usage::class);
    }

    /**
     * Get the stock movements for this item.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get the attachments for this item.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get the type from category.
     */
    public function getTypeAttribute(): string
    {
        return $this->category?->type ?? 'bahan';
    }

    /**
     * Check if the item is alat (equipment).
     */
    public function isAlat(): bool
    {
        return $this->type === 'alat';
    }

    /**
     * Check if the item is bahan (consumable).
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
     * Scope a query to only alat items.
     */
    public function scopeAlat($query)
    {
        return $query->whereHas('category', function ($q) {
            $q->where('type', 'alat');
        });
    }

    /**
     * Scope a query to only bahan items.
     */
    public function scopeBahan($query)
    {
        return $query->whereHas('category', function ($q) {
            $q->where('type', 'bahan');
        });
    }

    /**
     * Scope a query to only low stock items.
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<', 'minimum_stock');
    }

    /**
     * Check if the item (e.g. a consumable) is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    /**
     * An item needs calibration if it is alat and at least one of its units
     * has a calibration due within the next 30 days (or overdue).
     */
    public function needsCalibration(): bool
    {
        if (! $this->isAlat()) {
            return false;
        }

        return $this->units()
            ->whereNotNull('next_calibration_date')
            ->where('next_calibration_date', '<=', now()->addDays(30))
            ->exists();
    }
}
