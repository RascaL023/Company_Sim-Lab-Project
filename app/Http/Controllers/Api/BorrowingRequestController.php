<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BorrowingRequestResource;
use App\Models\BorrowingRequest;
use App\Models\Item;
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

        $validator->after(function ($validator) {
            $data = $validator->getData();
            $items = $data['items'] ?? [];

            if (! is_array($items)) {
                return;
            }

            foreach ($items as $index => $row) {
                if (! isset($row['item_id'])) {
                    continue;
                }

                $item = Item::find($row['item_id']);
                if (! $item) {
                    continue;
                }

                // Contract: alat dipinjam per unit (quantity harus 1); unit fisik dipilih
                // saat checkout, bukan saat request. Bahan cukup item_id + quantity.
                if ($item->isAlat() && (float) ($row['quantity'] ?? 0) !== 1.0) {
                    $validator->errors()->add(
                        "items.{$index}.quantity",
                        'Alat dipinjam per unit; quantity harus tepat 1.'
                    );
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $validated = $validator->validated();

        Gate::authorize('create', [BorrowingRequest::class, (int) $validated['requested_by']]);

        $requestNumber = 'BR-'.now()->format('Y').sprintf('%04d', rand(1000, 9999));

        $borrowingRequest = BorrowingRequest::create([
            'request_number' => $requestNumber,
            'requested_by' => $validated['requested_by'],
            'purpose' => $validated['purpose'],
            'status' => 'diajukan',
            'requested_at' => now(),
        ]);

        foreach ($validated['items'] as $itemData) {
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
