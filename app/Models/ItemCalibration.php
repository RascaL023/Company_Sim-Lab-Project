<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemCalibration extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'item_id',
        'calibration_date',
        'next_calibration_date',
        'calibrated_by',
        'certificate_number',
        'result',
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
            'result' => 'string',
            'calibration_date' => 'date',
            'next_calibration_date' => 'date',
            'recorded_by' => 'integer',
        ];
    }

    /**
     * Get the item that owns the calibration.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the user that recorded the calibration.
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Scope a query to only passed calibrations.
     */
    public function scopeLulus($query)
    {
        return $query->where('result', 'lulus');
    }

    /**
     * Scope a query to only failed calibrations.
     */
    public function scopeTidakLulus($query)
    {
        return $query->where('result', 'tidak_lulus');
    }

    /**
     * Scope a query to only upcoming calibrations.
     */
    public function scopeUpcoming($query)
    {
        return $query->whereNotNull('next_calibration_date')
            ->where('next_calibration_date', '>=', now()->toDateString())
            ->where('next_calibration_date', '<=', now()->addMonth()->toDateString());
    }

    /**
     * Scope a query to only overdue calibrations.
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotNull('next_calibration_date')
            ->where('next_calibration_date', '<', now()->toDateString());
    }
}