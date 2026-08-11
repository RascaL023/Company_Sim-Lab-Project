<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BorrowingItemResource;
use App\Models\BorrowingItem;
use App\Models\ItemUnit;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class BorrowingItemController extends Controller
{
    public function checkout(Request $request, BorrowingItem $borrowingItem)
    {
        Gate::authorize('checkout', $borrowingItem);

        $parent = $borrowingItem->borrowingRequest;

        if ($parent->status !== 'disetujui') {
            return response()->json([
                'message' => 'Item hanya bisa di-checkout jika permintaan sudah disetujui.',
            ], 422);
        }

        if ($borrowingItem->borrow_date !== null) {
            return response()->json([
                'message' => 'Item ini sudah di-checkout sebelumnya.',
            ], 422);
        }

        $isAlat = $borrowingItem->item->isAlat();
        $item = $borrowingItem->item;
        $quantity = $borrowingItem->quantity;

        $rules = [
            'expected_return_date' => 'required|date|after:now',
            'item_unit_id' => $isAlat ? 'required|exists:item_units,id' : 'prohibited',
        ];

        $messages = [
            'item_unit_id.required' => 'Pilih unit fisik yang akan di-checkout.',
            'item_unit_id.prohibited' => 'Item bahan tidak menggunakan unit fisik.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($isAlat) {
            $validator->after(function ($validator) use ($borrowingItem) {
                $data = $validator->getData();

                if (! isset($data['item_unit_id'])) {
                    $validator->errors()->add('item_unit_id', 'Pilih unit fisik yang akan di-checkout.');

                    return;
                }

                $unit = ItemUnit::find($data['item_unit_id']);

                if (! $unit) {
                    $validator->errors()->add('item_unit_id', 'Unit yang dipilih tidak valid.');

                    return;
                }

                if ($unit->item_id !== $borrowingItem->item_id) {
                    $validator->errors()->add('item_unit_id', 'Unit yang dipilih bukan milik item ini.');

                    return;
                }

                if (in_array($unit->condition, ['hilang', 'dihapus'])) {
                    $validator->errors()->add('item_unit_id', 'Unit dengan status hilang atau dihapus tidak dapat di-checkout.');

                    return;
                }

                $active = BorrowingItem::query()
                    ->where('item_unit_id', $data['item_unit_id'])
                    ->where('id', '!=', $borrowingItem->id)
                    ->whereNotNull('borrow_date')
                    ->whereNull('actual_return_date')
                    ->exists();

                if ($active) {
                    $validator->errors()->add('item_unit_id', 'Unit ini sedang dipinjam pada peminjaman lain.');

                    return;
                }
            });
        }

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $validated = $validator->validated();

        // Mutate stock BEFORE marking the item as checked out, inside a single
        // transaction, so an insufficient-stock rejection leaves no partial state.
        return DB::transaction(function () use ($request, $borrowingItem, $parent, $isAlat, $item, $quantity, $validated) {
            app(StockService::class)->record($item, 'out_borrow', (float) $quantity, [
                'item_unit_id' => $isAlat ? $validated['item_unit_id'] : null,
                'reference_type' => BorrowingItem::class,
                'reference_id' => $borrowingItem->id,
                'performed_by' => $request->user()->id,
                'notes' => $isAlat ? 'Peminjaman alat via checkout' : 'Peminjaman bahan via checkout',
                'occurred_at' => now(),
            ]);

            $borrowingItem->update([
                'item_unit_id' => $isAlat ? $validated['item_unit_id'] : null,
                'borrow_date' => now(),
                'checked_out_by' => $request->user()->id,
                'expected_return_date' => $validated['expected_return_date'],
            ]);

            $parent->refresh();
            $pendingCheckout = $parent->items()->whereNull('borrow_date')->exists();

            if (! $pendingCheckout && $parent->canTransitionTo('diproses')) {
                $parent->update(['status' => 'diproses']);
            }

            return new BorrowingItemResource(
                $borrowingItem->fresh()->load(['item', 'itemUnit', 'checkedOutBy', 'borrowingRequest'])
            );
        });
    }

    public function returnItem(Request $request, BorrowingItem $borrowingItem)
    {
        Gate::authorize('returnItem', $borrowingItem);

        $parent = $borrowingItem->borrowingRequest;

        if ($parent->status !== 'diproses') {
            return response()->json([
                'message' => 'Item hanya bisa dikembalikan jika permintaan sedang diproses.',
            ], 422);
        }

        if ($borrowingItem->borrow_date === null) {
            return response()->json([
                'message' => 'Item belum di-checkout, tidak bisa dikembalikan.',
            ], 422);
        }

        if ($borrowingItem->actual_return_date !== null) {
            return response()->json([
                'message' => 'Item ini sudah dikembalikan sebelumnya.',
            ], 422);
        }

        $isAlat = $borrowingItem->item->isAlat();
        $item = $borrowingItem->item;
        $quantity = $borrowingItem->quantity;

        $validated = $request->validate([
            'condition_after' => 'required|in:baik,rusak_ringan,rusak_berat,hilang',
            'is_damaged' => 'required|boolean',
            'check_notes' => 'nullable|string',
            'damage_notes' => 'required_if:is_damaged,true|nullable|string',
        ]);

        return DB::transaction(function () use ($request, $borrowingItem, $parent, $isAlat, $item, $quantity, $validated) {
            // Returning bahan restocks; for alat the movement is recorded without
            // touching numeric stock. Done before the row update for consistency.
            app(StockService::class)->record($item, 'in_return', (float) $quantity, [
                'item_unit_id' => $isAlat ? $borrowingItem->item_unit_id : null,
                'reference_type' => BorrowingItem::class,
                'reference_id' => $borrowingItem->id,
                'performed_by' => $request->user()->id,
                'notes' => $isAlat ? 'Pengembalian alat via return' : 'Pengembalian bahan via return',
                'occurred_at' => now(),
            ]);

            $borrowingItem->update([
                'condition_after' => $validated['condition_after'],
                'is_damaged' => $validated['is_damaged'],
                'damage_notes' => $validated['damage_notes'] ?? null,
                'check_notes' => $validated['check_notes'] ?? null,
                'actual_return_date' => now(),
                'checked_by' => $request->user()->id,
                'checked_at' => now(),
                'checked_in_by' => $request->user()->id,
            ]);

            $parent->refresh();
            $pendingReturn = $parent->items()->whereNull('actual_return_date')->exists();

            if (! $pendingReturn && $parent->canTransitionTo('selesai')) {
                $parent->update(['status' => 'selesai']);
            }

            return new BorrowingItemResource(
                $borrowingItem->fresh()->load(['item', 'itemUnit', 'checkedBy', 'checkedInBy', 'borrowingRequest'])
            );
        });
    }
}
