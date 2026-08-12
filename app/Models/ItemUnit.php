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
        'location_id',
        'purchase_date',
        // Kalibrasi bersifat opsional — hanya diisi untuk alat yang memang
        // memerlukan kalibrasi. ItemUnit tanpa tanggal kalibrasi bukan unit
        // yang invalid.
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
            'location_id' => 'integer',
            'purchase_date' => 'date',
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
     * Get the rack/room location for this unit.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
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
     * Active (not yet returned) borrowing for this unit, if any.
     */
    public function activeBorrowing()
    {
        return $this->hasOne(BorrowingItem::class, 'item_unit_id')
            ->whereNotNull('borrow_date')
            ->whereNull('actual_return_date');
    }

    /**
     * Whether this unit is currently checked out (on loan).
     * Reads the withExists attribute when eager-loaded, else queries.
     */
    public function getIsBorrowedAttribute(): bool
    {
        if (array_key_exists('is_borrowed', $this->attributes)) {
            return (bool) $this->attributes['is_borrowed'];
        }

        return $this->activeBorrowing()->exists();
    }

    /**
     * Get the attachments for this unit.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Units that can be checked out: not lost/deleted and not on active loan.
     * Matches GET /items/{id}/units?available=1.
     */
    public function scopeAvailable($query)
    {
        return $query
            ->whereNotIn('condition', ['hilang', 'dihapus'])
            ->whereDoesntHave('activeBorrowing');
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
     * A unit without a calibration schedule (null next_calibration_date) is
     * simply not tracked for calibration and is never flagged here.
     */
    public function scopeNeedsCalibration($query)
    {
        return $query->whereNotNull('next_calibration_date')
            ->where('next_calibration_date', '<', now()->toDateString());
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
