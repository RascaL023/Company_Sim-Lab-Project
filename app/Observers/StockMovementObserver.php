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
        $stockMovement->item?->update([
            'stock_quantity' => $stockMovement->quantity_after,
        ]);
    }
}
