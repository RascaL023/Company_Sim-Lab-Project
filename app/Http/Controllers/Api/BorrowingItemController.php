<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BorrowingItemResource;
use App\Models\BorrowingItem;
use App\Models\ItemUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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

        $rules = [
            'expected_return_date' => 'required|date|after:now',
            'item_unit_id' => $isAlat ? ['required'] : ['nullable'],
        ];

        if ($isAlat) {
            $rules['item_unit_id'][] = 'exists:item_units,id';
            $rules['item_unit_id'][] = function ($attribute, $value, $fail) use ($borrowingItem) {
                $unit = ItemUnit::find($value);

                if (! $unit) {
                    return;
                }

                if ($unit->item_id !== $borrowingItem->item_id) {
                    $fail('Unit yang dipilih bukan milik item ini.');
                }

                if (in_array($unit->condition, ['hilang', 'dihapus'])) {
                    $fail('Unit dengan status hilang atau dihapus tidak dapat di-checkout.');
                }

                $active = BorrowingItem::query()
                    ->where('item_unit_id', $value)
                    ->where('id', '!=', $borrowingItem->id)
                    ->whereNotNull('borrow_date')
                    ->whereNull('actual_return_date')
                    ->exists();

                if ($active) {
                    $fail('Unit ini sedang dipinjam pada peminjaman lain.');
                }
            };
        }

        $validated = $request->validate($rules, [
            'item_unit_id.required' => 'Pilih unit fisik yang akan di-checkout.',
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

        $validated = $request->validate([
            'condition_after' => 'required|in:baik,rusak_ringan,rusak_berat,hilang',
            'is_damaged' => 'required|boolean',
            'check_notes' => 'nullable|string',
            'damage_notes' => 'required_if:is_damaged,true|nullable|string',
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
    }
}
