<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsageResource;
use App\Models\Item;
use App\Models\Usage;
use Illuminate\Http\Request;

class UsageController extends Controller
{
    public function index(Request $request)
    {
        $query = Usage::with(['item', 'itemUnit', 'user', 'verifiedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('item_id')) {
            $query->where('item_id', $request->get('item_id'));
        }

        return $query->orderBy('created_at', 'desc')->paginate($request->query('per_page', 15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'item_unit_id' => 'nullable|exists:item_units,id',
            'quantity_used' => 'required|numeric|min:0.01',
            'purpose' => 'required|string',
        ]);

        $item = Item::find($validated['item_id']);
        $validated['user_id'] = $request->user()->id;
        $validated['quantity_before'] = $item->stock_quantity;
        $validated['quantity_after'] = max(0, $item->stock_quantity - $validated['quantity_used']);
        $validated['status'] = 'dicatat';

        $usage = Usage::create($validated);

        $item->decrement('stock_quantity', $validated['quantity_used']);

        return (new UsageResource($usage))->response()->setStatusCode(201);
    }

    public function show(Usage $usage)
    {
        return new UsageResource($usage);
    }

    public function update(Request $request, Usage $usage)
    {
        $request->validate([
            'status' => 'sometimes|in:dicatat,diverifikasi,ditolak',
            'verified_by' => 'nullable|exists:users,id',
            'rejection_reason' => 'nullable|string',
        ]);

        $usage->update($request->only(['status', 'verified_by', 'rejection_reason']));

        return new UsageResource($usage);
    }

    public function destroy(Usage $usage)
    {
        $usage->delete();

        return response()->json(null, 204);
    }

    public function verify(Request $request, Usage $usage)
    {
        $usage->update([
            'status' => 'diverifikasi',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return new UsageResource($usage);
    }

    public function reject(Request $request, Usage $usage)
    {
        $validated = $request->validate(['rejection_reason' => 'required|string']);

        $usage->update([
            'status' => 'ditolak',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return new UsageResource($usage);
    }
}
