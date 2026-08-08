<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockMovementResource;
use App\Models\Item;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = StockMovement::with(['item', 'itemUnit', 'performedBy']);

        if ($request->filled('type')) {
            $types = explode(',', $request->get('type'));
            $query->whereIn('type', $types);
        }

        if ($request->filled('item_id')) {
            $query->where('item_id', $request->get('item_id'));
        }

        return $query->orderBy('occurred_at', 'desc')->paginate($request->query('per_page', 15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'item_unit_id' => 'nullable|exists:item_units,id',
            'type' => 'required|in:in_purchase,in_return,in_adjustment,out_borrow,out_usage,out_disposal,out_adjustment,transfer_in,transfer_out',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        $item = Item::find($validated['item_id']);
        $quantityBefore = $item->stock_quantity;

        $isIncoming = in_array($validated['type'], ['in_purchase', 'in_return', 'in_adjustment', 'transfer_in']);
        $quantityAfter = $isIncoming ? $quantityBefore + $validated['quantity'] : max(0, $quantityBefore - $validated['quantity']);

        $validated['quantity_before'] = $quantityBefore;
        $validated['quantity_after'] = $quantityAfter;
        $validated['performed_by'] = $request->user()->id;
        $validated['occurred_at'] = $validated['occurred_at'] ?? now();

        $movement = StockMovement::create($validated);

        $item->update(['stock_quantity' => $quantityAfter]);

        return (new StockMovementResource($movement))->response()->setStatusCode(201);
    }

    public function show(StockMovement $stockMovement)
    {
        return new StockMovementResource($stockMovement);
    }

    public function update(Request $request, StockMovement $stockMovement)
    {
        return $stockMovement->update($request->only(['quantity', 'notes', 'occurred_at']))
            ? new StockMovementResource($stockMovement)
            : response()->json(['message' => 'Update failed'], 500);
    }

    public function destroy(StockMovement $stockMovement)
    {
        $stockMovement->delete();

        return response()->json(null, 204);
    }
}
