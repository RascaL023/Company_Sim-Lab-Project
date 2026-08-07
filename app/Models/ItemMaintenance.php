<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemMaintenance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'item_id',
        'maintenance_date',
        'description',
        'performed_by',
        'cost',
        'status',
        'notes',
        'recorded_by',
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
            'maintenance_date' => 'date',
            'cost' => 'decimal:2',
            'recorded_by' => 'integer',
        ];
    }

    /**
     * Get the item that owns the maintenance.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the user that recorded the maintenance.
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Scope a query to only completed maintenances.
     */
    public function scopeSelesai($query)
    {
        return $query->where('status', 'selesai');
    }

    /**
     * Scope a query to only ongoing maintenances.
     */
    public function scopeProses($query)
    {
        return $query->where('status', 'proses');
    }

    /**
     * Scope a query to only pending maintenances.
     */
    public function scopeTertunda($query)
    {
        return $query->where('status', 'tertunda');
    }

    /**
     * Scope a query to only maintenances this month.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('maintenance_date', '=', now()->month)
            ->whereYear('maintenance_date', '=', now()->year);
    }
}