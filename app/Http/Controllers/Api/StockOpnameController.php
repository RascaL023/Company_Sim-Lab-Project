<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockOpnameResource;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\StockOpname;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StockOpnameController extends Controller
{
    public function store(Request $request)
    {
        Gate::authorize('create', StockOpname::class);

        $validated = $request->validate([
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer|distinct|exists:items,id',
            'items.*.counted_quantity' => 'required|numeric|min:0',
        ]);

        $opname = DB::transaction(function () use ($request, $validated) {
            $opname = StockOpname::create([
                'performed_by' => $request->user()->id,
                'notes' => $validated['notes'] ?? null,
                'created_at' => now(),
            ]);

            $adjusted = 0;
            $unchanged = 0;
            $sessionNote = $validated['notes'] ?? null;

            foreach ($validated['items'] as $line) {
                $item = Item::query()->lockForUpdate()->findOrFail($line['item_id']);
                $quantityBefore = (float) $item->stock_quantity;
                $counted = (float) $line['counted_quantity'];
                $diff = round($counted - $quantityBefore, 2);

                if (abs($diff) < 0.00001) {
                    $unchanged++;

                    continue;
                }

                $type = $diff > 0 ? 'in_adjustment' : 'out_adjustment';
                $quantity = abs($diff);
                $quantityAfter = $counted;

                $movementNote = 'Hasil stock opname #'.$opname->id;
                if ($sessionNote) {
                    $movementNote .= ': '.$sessionNote;
                }

                StockMovement::create([
                    'item_id' => $item->id,
                    'item_unit_id' => null,
                    'type' => $type,
                    'quantity' => $quantity,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $quantityAfter,
                    'reference_type' => StockOpname::class,
                    'reference_id' => $opname->id,
                    'performed_by' => $request->user()->id,
                    'notes' => $movementNote,
                    'occurred_at' => now(),
                ]);

                $adjusted++;
            }

            $opname->summary = [
                'items_submitted' => count($validated['items']),
                'items_adjusted' => $adjusted,
                'items_unchanged' => $unchanged,
            ];

            return $opname;
        });

        $opname->load(['performer', 'movements.item', 'movements.performer']);

        return (new StockOpnameResource($opname))
            ->response()
            ->setStatusCode(201);
    }
}
