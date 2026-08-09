<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsageResource;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Usage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UsageController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Usage::class);

        $query = Usage::with(['item', 'itemUnit', 'user', 'verifiedBy']);

        if (! $request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('item_id')) {
            $query->where('item_id', $request->get('item_id'));
        }

        return UsageResource::collection(
            $query->orderBy('created_at', 'desc')->paginate($request->query('per_page', 15))
        );
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Usage::class);

        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'item_unit_id' => 'nullable|exists:item_units,id',
            'quantity_used' => 'required|numeric|min:0.01',
            'purpose' => 'required|string',
        ]);

        $item = Item::findOrFail($validated['item_id']);
        $quantityBefore = $item->stock_quantity;
        $quantityAfter = max(0, $quantityBefore - $validated['quantity_used']);

        $validated['user_id'] = $request->user()->id;
        $validated['quantity_before'] = $quantityBefore;
        $validated['quantity_after'] = $quantityAfter;
        $validated['status'] = 'dicatat';
        $validated['usage_date'] = now();

        $usage = Usage::create($validated);

        StockMovement::create([
            'item_id' => $usage->item_id,
            'item_unit_id' => $usage->item_unit_id,
            'type' => 'out_usage',
            'quantity' => $validated['quantity_used'],
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'reference_type' => Usage::class,
            'reference_id' => $usage->id,
            'performed_by' => $request->user()->id,
            'notes' => $validated['purpose'],
            'occurred_at' => now(),
        ]);

        return (new UsageResource($usage))->response()->setStatusCode(201);
    }

    public function show(Usage $usage)
    {
        Gate::authorize('view', $usage);

        return new UsageResource($usage);
    }

    public function update(Request $request, Usage $usage)
    {
        Gate::authorize('update', $usage);

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
        Gate::authorize('delete', $usage);

        $usage->delete();

        return response()->json(null, 204);
    }

    public function verify(Request $request, Usage $usage)
    {
        Gate::authorize('verify', $usage);

        $usage->update([
            'status' => 'diverifikasi',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return new UsageResource($usage);
    }

    public function reject(Request $request, Usage $usage)
    {
        Gate::authorize('reject', $usage);

        $validated = $request->validate(['rejection_reason' => 'required|string']);

        $usage->update([
            'status' => 'ditolak',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return new UsageResource($usage);
    }
}
