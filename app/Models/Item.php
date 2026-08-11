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
        'location_id',
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
     * Boot the model.
     */
    protected static function booted()
    {
        static::creating(function ($item) {
            $item->enforceDomainRules();
        });

        static::updating(function ($item) {
            $item->enforceDomainRules();
        });
    }

    /**
     * Enforce domain rules based on item type.
     */
    protected function enforceDomainRules()
    {
        // If category is not loaded, load it
        if (! $this->relationLoaded('category')) {
            $this->load('category');
        }

        if ($this->category) {
            if ($this->category->type === 'alat') {
                // For alat, stock_quantity, minimum_stock, and location_id must be null
                $this->stock_quantity = null;
                $this->minimum_stock = null;
                $this->location_id = null;
            } elseif ($this->category->type === 'bahan') {
                // For bahan, ensure stock_quantity and minimum_stock are not null and >=0
                // Note: validation should have ensured they are present and numeric, but we double-check
                if ($this->stock_quantity === null) {
                    $this->stock_quantity = 0; // or throw an exception? We'll set to 0 as fallback, but validation should prevent null
                }
                if ($this->minimum_stock === null) {
                    $this->minimum_stock = 0;
                }
                // location_id should already be set by validation, but if not, we cannot set it because we don't know the location
                // We'll leave it as is and let validation catch it
            }
        }
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
     * Get the location of this item (hanya untuk bahan; alat = NULL,
     * lokasi fisik alat berada di item_units.location_id).
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
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
