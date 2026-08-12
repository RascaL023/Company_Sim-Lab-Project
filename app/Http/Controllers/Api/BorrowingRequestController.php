<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BorrowingRequestResource;
use App\Models\BorrowingRequest;
use App\Models\Item;
use App\Models\ItemUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class BorrowingRequestController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', BorrowingRequest::class);

        return BorrowingRequestResource::collection(
            BorrowingRequest::with(['requestedBy', 'approvedBy', 'items.item', 'items.itemUnit'])
                ->when(! $request->user()->canViewAllLabRecords(), fn ($q) => $q->where('requested_by', $request->user()->id))
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
                ->paginate($request->query('per_page', 15))
        );
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'requested_by' => 'required|exists:users,id',
            'purpose' => 'required|string',
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.item_unit_id' => 'prohibited',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $validated = $validator->validated();

        Gate::authorize('create', [BorrowingRequest::class, (int) $validated['requested_by']]);

        $domainErrors = [];
        foreach ($validated['items'] as $index => $row) {
            $item = Item::find($row['item_id']);
            if (! $item) {
                continue;
            }

            $qty = (float) ($row['quantity'] ?? 0);

            // Alat: jumlah = berapa unit diminta. Unit fisik dipilih laboran saat checkout.
            // Disimpan sebagai N baris quantity=1 (satu unit per baris).
            if ($item->isAlat()) {
                if ($qty < 1 || floor($qty) !== $qty) {
                    $domainErrors["items.{$index}.quantity"][] = 'Jumlah alat harus bilangan bulat minimal 1.';

                    continue;
                }

                $available = ItemUnit::query()
                    ->where('item_id', $item->id)
                    ->available()
                    ->count();

                if ($qty > $available) {
                    $domainErrors["items.{$index}.quantity"][] =
                        "Unit tersedia tidak mencukupi. Tersedia: {$available}, diminta: {$qty}.";
                }
            }

            if ($item->isBahan()) {
                $stock = (float) ($item->stock_quantity ?? 0);
                if ($qty > $stock) {
                    $domainErrors["items.{$index}.quantity"][] =
                        "Stok tidak mencukupi. Tersedia: {$stock}, diminta: {$qty}.";
                }
            }
        }

        if ($domainErrors !== []) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $domainErrors,
            ], 422);
        }

        $requestNumber = 'BR-'.now()->format('Y').sprintf('%04d', rand(1000, 9999));

        $borrowingRequest = BorrowingRequest::create([
            'request_number' => $requestNumber,
            'requested_by' => $validated['requested_by'],
            'purpose' => $validated['purpose'],
            'status' => 'diajukan',
            'requested_at' => now(),
        ]);

        foreach ($validated['items'] as $itemData) {
            $item = Item::find($itemData['item_id']);
            $qty = (float) $itemData['quantity'];

            if ($item && $item->isAlat()) {
                $count = (int) $qty;
                for ($i = 0; $i < $count; $i++) {
                    $borrowingRequest->items()->create([
                        'item_id' => $itemData['item_id'],
                        'quantity' => 1,
                        'condition_before' => 'baik',
                    ]);
                }

                continue;
            }

            $borrowingRequest->items()->create([
                'item_id' => $itemData['item_id'],
                'quantity' => $itemData['quantity'],
                'condition_before' => 'baik',
            ]);
        }

        return (new BorrowingRequestResource(
            $borrowingRequest->load(['requestedBy', 'items.item'])
        ))->response()->setStatusCode(201);
    }

    public function show(BorrowingRequest $borrowingRequest)
    {
        Gate::authorize('view', $borrowingRequest);

        $borrowingRequest->load([
            'requestedBy',
            'approvedBy',
            'items.item',
            'items.itemUnit',
        ]);

        return new BorrowingRequestResource($borrowingRequest);
    }

    public function update(Request $request, BorrowingRequest $borrowingRequest)
    {
        Gate::authorize('update', $borrowingRequest);

        if ($request->exists('status')) {
            return response()->json([
                'message' => 'Field status tidak bisa diubah lewat endpoint update. Gunakan approve, reject, atau cancel.',
            ], 422);
        }

        $validated = $request->validate([
            'purpose' => 'sometimes|string',
            'notes' => 'nullable|string',
        ]);

        $borrowingRequest->update($validated);

        return new BorrowingRequestResource($borrowingRequest);
    }

    public function destroy(BorrowingRequest $borrowingRequest)
    {
        Gate::authorize('delete', $borrowingRequest);

        $borrowingRequest->update(['status' => 'batal']);

        return response()->json(null, 204);
    }

    public function approve(Request $request, BorrowingRequest $borrowingRequest)
    {
        Gate::authorize('approve', $borrowingRequest);

        if (! $borrowingRequest->canTransitionTo('disetujui')) {
            return response()->json([
                'message' => 'Permintaan dengan status saat ini tidak bisa disetujui.',
            ], 422);
        }

        $borrowingRequest->approved_by = $request->user()->id;
        $borrowingRequest->approved_at = now();
        $borrowingRequest->status = 'disetujui';
        $borrowingRequest->save();

        return new BorrowingRequestResource($borrowingRequest);
    }

    public function reject(Request $request, BorrowingRequest $borrowingRequest)
    {
        Gate::authorize('reject', $borrowingRequest);

        if (! $borrowingRequest->canTransitionTo('ditolak')) {
            return response()->json([
                'message' => 'Permintaan dengan status saat ini tidak bisa ditolak.',
            ], 422);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $borrowingRequest->approved_by = $request->user()->id;
        $borrowingRequest->rejected_at = now();
        $borrowingRequest->status = 'ditolak';
        $borrowingRequest->rejection_reason = $validated['rejection_reason'];
        $borrowingRequest->save();

        return new BorrowingRequestResource($borrowingRequest);
    }

    public function cancel(Request $request, BorrowingRequest $borrowingRequest)
    {
        Gate::authorize('cancel', $borrowingRequest);

        if (! $borrowingRequest->canTransitionTo('batal')) {
            return response()->json([
                'message' => 'Permintaan dengan status saat ini tidak bisa dibatalkan.',
            ], 422);
        }

        $borrowingRequest->update(['status' => 'batal']);

        return new BorrowingRequestResource($borrowingRequest);
    }
}
