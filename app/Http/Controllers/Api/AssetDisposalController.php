<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssetDisposalResource;
use App\Models\AssetDisposal;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AssetDisposalController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', AssetDisposal::class);

        $query = AssetDisposal::query()
            ->with(['item', 'itemUnit.item', 'proposer', 'reviewer'])
            ->orderByDesc('proposed_at');

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $disposals = $query->paginate($request->query('per_page', 20));

        return AssetDisposalResource::collection($disposals);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', AssetDisposal::class);

        $validated = $request->validate([
            'item_id' => 'nullable|integer|exists:items,id',
            'item_unit_id' => 'nullable|integer|exists:item_units,id',
            'reason' => 'required|in:rusak_total,kedaluwarsa,hilang,lainnya',
            'notes' => 'nullable|string',
        ]);

        $hasItem = array_key_exists('item_id', $validated) && $validated['item_id'] !== null;
        $hasUnit = array_key_exists('item_unit_id', $validated) && $validated['item_unit_id'] !== null;

        if ($hasItem === $hasUnit) {
            throw ValidationException::withMessages([
                'item_id' => ['Isi tepat salah satu: item_id (bahan) atau item_unit_id (alat).'],
            ]);
        }

        if ($hasUnit) {
            $validated['item_id'] = null;
        }

        $disposal = AssetDisposal::create([
            ...$validated,
            'proposed_by' => $request->user()->id,
            'proposed_at' => now(),
            'status' => 'diusulkan',
        ]);

        return (new AssetDisposalResource(
            $disposal->load(['item', 'itemUnit', 'proposer'])
        ))->response()->setStatusCode(201);
    }

    public function approve(Request $request, AssetDisposal $assetDisposal)
    {
        Gate::authorize('approve', $assetDisposal);

        if (! $assetDisposal->canTransitionTo('disetujui')) {
            return response()->json([
                'message' => 'Usulan dengan status saat ini tidak bisa disetujui.',
            ], 422);
        }

        DB::transaction(function () use ($request, $assetDisposal) {
            $assetDisposal->update([
                'status' => 'disetujui',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            if ($assetDisposal->item_unit_id) {
                ItemUnit::query()
                    ->whereKey($assetDisposal->item_unit_id)
                    ->update(['condition' => 'dihapus']);

                return;
            }

            $item = Item::query()->lockForUpdate()->findOrFail($assetDisposal->item_id);
            $quantityBefore = (float) $item->stock_quantity;

            if ($quantityBefore > 0) {
                StockMovement::create([
                    'item_id' => $item->id,
                    'item_unit_id' => null,
                    'type' => 'out_disposal',
                    'quantity' => $quantityBefore,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => 0,
                    'reference_type' => AssetDisposal::class,
                    'reference_id' => $assetDisposal->id,
                    'performed_by' => $request->user()->id,
                    'notes' => 'Penghapusan aset disetujui (disposal #'.$assetDisposal->id.')',
                    'occurred_at' => now(),
                ]);
            } else {
                $item->update(['stock_quantity' => 0]);
            }
        });

        return new AssetDisposalResource(
            $assetDisposal->fresh()->load(['item', 'itemUnit', 'proposer', 'reviewer'])
        );
    }

    public function reject(Request $request, AssetDisposal $assetDisposal)
    {
        Gate::authorize('reject', $assetDisposal);

        if (! $assetDisposal->canTransitionTo('ditolak')) {
            return response()->json([
                'message' => 'Usulan dengan status saat ini tidak bisa ditolak.',
            ], 422);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $assetDisposal->update([
            'status' => 'ditolak',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return new AssetDisposalResource(
            $assetDisposal->fresh()->load(['item', 'itemUnit', 'proposer', 'reviewer'])
        );
    }
}
