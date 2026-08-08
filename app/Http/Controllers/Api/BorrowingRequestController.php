<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BorrowingRequestResource;
use App\Models\BorrowingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BorrowingRequestController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', BorrowingRequest::class);

        return BorrowingRequest::with(['requestedBy', 'approvedBy', 'items.item'])
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('requested_by', $request->user()->id))
            ->when($request->filled('status'), fn ($q, $s) => $q->where('status', $s))
            ->paginate($request->query('per_page', 15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'requested_by' => 'required|exists:users,id',
            'purpose' => 'required|string',
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:1',
        ]);

        Gate::authorize('create', [BorrowingRequest::class, (int) $validated['requested_by']]);

        $requestNumber = 'BR-'.now()->format('Y').sprintf('%04d', rand(1000, 9999));

        $request = BorrowingRequest::create([
            'request_number' => $requestNumber,
            'requested_by' => $validated['requested_by'],
            'purpose' => $validated['purpose'],
            'status' => 'diajukan',
        ]);

        foreach ($validated['items'] as $itemData) {
            $request->items()->create([
                'item_id' => $itemData['item_id'],
                'quantity' => $itemData['quantity'],
                'condition_before' => 'baik',
            ]);
        }

        return (new BorrowingRequestResource($request))->response()->setStatusCode(201);
    }

    public function show(BorrowingRequest $borrowingRequest)
    {
        Gate::authorize('view', $borrowingRequest);

        return new BorrowingRequestResource($borrowingRequest);
    }

    public function update(Request $request, BorrowingRequest $borrowingRequest)
    {
        Gate::authorize('update', $borrowingRequest);

        $request->validate([
            'status' => 'sometimes|in:diajukan,disetujui,ditolak,diproses,selesai,batal',
            'approved_by' => 'sometimes|exists:users,id',
            'rejection_reason' => 'nullable|string',
        ]);

        $borrowingRequest->update($request->only(['status', 'approved_by', 'rejection_reason']));

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

        $borrowingRequest->approved_by = $request->user()->id;
        $borrowingRequest->approved_at = now();
        $borrowingRequest->status = 'disetujui';
        $borrowingRequest->save();

        return new BorrowingRequestResource($borrowingRequest);
    }

    public function reject(Request $request, BorrowingRequest $borrowingRequest)
    {
        Gate::authorize('reject', $borrowingRequest);

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
}
