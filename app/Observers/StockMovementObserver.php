<?php

namespace App\Observers;

use App\Models\StockMovement;

class StockMovementObserver
{
    /**
     * Sync catalog stock from the ledger entry after it is appended.
     */
    public function created(StockMovement $stockMovement): void
    {
        // Only update stock_quantity for bahan items; for alat, stock_quantity remains NULL
        $item = $stockMovement->item;
        if ($item && ! $item->isAlat()) {
            $item->update([
                'stock_quantity' => $stockMovement->quantity_after,
            ]);
        }
    }
}
