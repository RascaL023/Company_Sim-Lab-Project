<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDisposal extends Model
{
    use HasFactory;

    private const array TRANSITIONS = [
        'diusulkan' => ['disetujui', 'ditolak'],
        'disetujui' => [],
        'ditolak' => [],
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'item_id',
        'item_unit_id',
        'reason',
        'notes',
        'proposed_by',
        'proposed_at',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'item_id' => 'integer',
            'item_unit_id' => 'integer',
            'proposed_by' => 'integer',
            'reviewed_by' => 'integer',
            'proposed_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'reason' => 'string',
            'status' => 'string',
        ];
    }

    public function canTransitionTo(string $status): bool
    {
        $allowed = self::TRANSITIONS[$this->status] ?? [];

        return in_array($status, $allowed, true);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function itemUnit(): BelongsTo
    {
        return $this->belongsTo(ItemUnit::class, 'item_unit_id');
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isForUnit(): bool
    {
        return $this->item_unit_id !== null;
    }

    public function isForItem(): bool
    {
        return $this->item_id !== null && $this->item_unit_id === null;
    }
}
