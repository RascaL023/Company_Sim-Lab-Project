<?php

namespace App\Observers;

use App\Models\BorrowingItem;
use App\Models\ItemMaintenance;

class BorrowingItemObserver
{
    /**
     * When a borrowing item is marked damaged on return, open a maintenance ticket
     * for the physical unit (alat). Bahan without item_unit_id are skipped.
     */
    public function updated(BorrowingItem $borrowingItem): void
    {
        if (! $borrowingItem->wasChanged('is_damaged') || ! $borrowingItem->is_damaged) {
            return;
        }

        if ($borrowingItem->item_unit_id === null) {
            return;
        }

        ItemMaintenance::create([
            'item_unit_id' => $borrowingItem->item_unit_id,
            'maintenance_date' => now()->toDateString(),
            'description' => $borrowingItem->damage_notes ?: 'Kerusakan dilaporkan saat pengembalian peminjaman.',
            'status' => 'proses',
            'recorded_by' => $borrowingItem->checked_by,
            'notes' => 'Dibuat otomatis dari pengembalian borrowing_item #'.$borrowingItem->id,
        ]);
    }
}
