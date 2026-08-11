<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsageResource;
use App\Models\Item;
use App\Models\Usage;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UsageController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Usage::class);

        $query = Usage::with(['item', 'itemUnit', 'user', 'verifiedBy']);

        if (! $request->user()->canViewAllLabRecords()) {
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

        if ($item->isBahan()) {
            // Bahan NEVER uses a physical unit.
            if (! empty($validated['item_unit_id'])) {
                return response()->json([
                    'message' => 'Item bahan tidak menggunakan unit fisik.',
                    'errors' => ['item_unit_id' => ['Item bahan tidak menggunakan unit fisik.']],
                ], 422);
            }
            $validated['item_unit_id'] = null;
        } else {
            // Alat has no numeric stock; usage is recorded without stock mutation.
            $validated['item_unit_id'] = null;
        }

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'dicatat';
        $validated['usage_date'] = now();

        try {
            $usage = DB::transaction(function () use ($item, $validated, $request) {
                $usage = Usage::create($validated);

                $movement = app(StockService::class)->record($item, 'out_usage', (float) $validated['quantity_used'], [
                    'item_unit_id' => $validated['item_unit_id'] ?? null,
                    'reference_type' => Usage::class,
                    'reference_id' => $usage->id,
                    'performed_by' => $request->user()->id,
                    'notes' => $validated['purpose'],
                    'occurred_at' => now(),
                ]);

                $usage->update([
                    'quantity_before' => $movement->quantity_before,
                    'quantity_after' => $movement->quantity_after,
                ]);

                return $usage;
            });
        } catch (ValidationException $e) {
            // Map the service's generic `quantity` key to the request field name.
            $errors = $e->errors();
            if (isset($errors['quantity'])) {
                $errors['quantity_used'] = $errors['quantity'];
                unset($errors['quantity']);
            }

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

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
