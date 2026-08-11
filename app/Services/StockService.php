<?php

namespace App\Services;

use App\Models\Item;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Centralized stock mutation service.
 *
 * All catalog stock changes for BAHAN items must go through this service so
 * that the ledger (stock_movements) and the cached catalog stock
 * (items.stock_quantity) stay consistent and every outgoing movement is
 * validated atomically.
 *
 * Rules:
 *  - ALAT items have no numeric stock; a movement is still recorded (with
 *    null before/after) but items.stock_quantity is never touched.
 *  - BAHAN items use a row lock (FOR UPDATE) so concurrent requests cannot
 *    both pass an availability check and drive stock negative.
 *  - Outgoing movements that exceed the available stock are rejected with a
 *    422 ValidationException. Stock is NEVER clamped with max(0, ...).
 */
class StockService
{
    /**
     * Outgoing movement types (reduce stock).
     */
    public const OUTGOING = ['out_borrow', 'out_usage', 'out_disposal', 'out_adjustment', 'transfer_out'];

    /**
     * Incoming movement types (increase stock).
     */
    public const INCOMING = ['in_purchase', 'in_return', 'in_adjustment', 'transfer_in'];

    public function isOutgoing(string $type): bool
    {
        return in_array($type, self::OUTGOING, true);
    }

    /**
     * Record a stock movement and atomically update the catalog stock.
     *
     * @param  array  $attributes  Extra columns: item_unit_id, reference_type,
     *                             reference_id, performed_by, notes, occurred_at.
     *                             quantity_before/quantity_after are computed here.
     */
    public function record(Item $item, string $type, float $quantity, array $attributes = []): StockMovement
    {
        if ($item->isAlat()) {
            return DB::transaction(function () use ($item, $type, $quantity, $attributes) {
                return StockMovement::create(array_merge($attributes, [
                    'item_id' => $item->id,
                    'type' => $type,
                    'quantity' => $quantity,
                    'quantity_before' => null,
                    'quantity_after' => null,
                ]));
            });
        }

        return DB::transaction(function () use ($item, $type, $quantity, $attributes) {
            $locked = Item::where('id', $item->id)->lockForUpdate()->firstOrFail();
            $quantityBefore = (float) ($locked->stock_quantity ?? 0);

            if ($this->isOutgoing($type)) {
                if ($quantityBefore < $quantity) {
                    throw ValidationException::withMessages([
                        'quantity' => [
                            'Stok tidak mencukupi. Tersedia: '.number_format($quantityBefore, 2, ',', '.').
                            ', diminta: '.number_format($quantity, 2, ',', '.').'.',
                        ],
                    ]);
                }
                $quantityAfter = $quantityBefore - $quantity;
            } else {
                $quantityAfter = $quantityBefore + $quantity;
            }

            return StockMovement::create(array_merge($attributes, [
                'item_id' => $item->id,
                'type' => $type,
                'quantity' => $quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
            ]));
        });
    }
}
