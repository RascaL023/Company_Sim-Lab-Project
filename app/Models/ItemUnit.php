<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemUnit extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'item_id',
        'serial_number',
        'asset_tag',
        'condition',
        'location',
        'purchase_date',
        'expiry_date',
        'next_calibration_date',
        'last_calibration_date',
        'notes',
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
            'condition' => 'string',
            'purchase_date' => 'date',
            'expiry_date' => 'date',
            'next_calibration_date' => 'date',
            'last_calibration_date' => 'date',
            'created_by' => 'integer',
        ];
    }

    /**
     * Get the item (catalog) that this unit belongs to.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the user who created this unit.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the calibrations for this unit.
     */
    public function calibrations(): HasMany
    {
        return $this->hasMany(ItemCalibration::class, 'item_unit_id');
    }

    /**
     * Get the maintenances for this unit.
     */
    public function maintenances(): HasMany
    {
        return $this->hasMany(ItemMaintenance::class, 'item_unit_id');
    }

    /**
     * Get the borrowing items for this unit.
     */
    public function borrowingItems(): HasMany
    {
        return $this->hasMany(BorrowingItem::class, 'item_unit_id');
    }

    /**
     * Alias kept for older call sites; borrowings now live on borrowing_items.
     */
    public function borrowings(): HasMany
    {
        return $this->borrowingItems();
    }

    /**
     * Get the attachments for this unit.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Scope a query to only units in good condition.
     */
    public function scopeBaik($query)
    {
        return $query->where('condition', 'baik');
    }

    /**
     * Scope a query to only units with light damage.
     */
    public function scopeRusakRingan($query)
    {
        return $query->where('condition', 'rusak_ringan');
    }

    /**
     * Scope a query to only units with heavy damage.
     */
    public function scopeRusakBerat($query)
    {
        return $query->where('condition', 'rusak_berat');
    }

    /**
     * Scope a query to only lost units.
     */
    public function scopeHilang($query)
    {
        return $query->where('condition', 'hilang');
    }

    /**
     * Scope a query to only units needing calibration.
     */
    public function scopeNeedsCalibration($query)
    {
        return $query->whereNotNull('next_calibration_date')
            ->where('next_calibration_date', '<', now()->toDateString());
    }

    /**
     * Scope a query to only expired units.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now()->toDateString());
    }

    /**
     * Check if the unit needs calibration.
     */
    public function needsCalibration(): bool
    {
        return ! is_null($this->next_calibration_date) &&
               $this->next_calibration_date < now()->toDateString();
    }

    /**
     * Check if the unit is expired.
     */
    public function isExpired(): bool
    {
        return ! is_null($this->expiry_date) &&
               $this->expiry_date < now()->toDateString();
    }

    /**
     * Check if the unit is in good condition.
     */
    public function isBaik(): bool
    {
        return $this->condition === 'baik';
    }

    /**
     * Check if the unit is damaged (any level).
     */
    public function isDamaged(): bool
    {
        return in_array($this->condition, ['rusak_ringan', 'rusak_berat', 'hilang']);
    }
}
