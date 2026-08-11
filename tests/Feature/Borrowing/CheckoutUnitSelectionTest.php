<?php

namespace Tests\Feature\Borrowing;

use App\Models\BorrowingItem;
use App\Models\BorrowingRequest;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutUnitSelectionTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    private function approvedAlatItem(): array
    {
        $laboran = User::factory()->laboran()->create();
        $peminjam = User::factory()->peminjam()->create();
        $item = Item::factory()->alat()->create();
        $request = BorrowingRequest::factory()->disetujui()->create(['requested_by' => $peminjam->id]);
        $borrowingItem = BorrowingItem::factory()->create([
            'borrowing_request_id' => $request->id,
            'item_id' => $item->id,
            'item_unit_id' => null,
            'quantity' => 1,
            'borrow_date' => null,
            'expected_return_date' => null,
            'actual_return_date' => null,
        ]);

        return [$laboran, $borrowingItem, $item];
    }

    public function test_checkout_alat_requires_item_unit_id(): void
    {
        [$laboran, $borrowingItem] = $this->approvedAlatItem();

        $this->withToken($this->tokenFor($laboran))
            ->patchJson("/api/borrowing-items/{$borrowingItem->id}/checkout", [
                'expected_return_date' => now()->addDays(3)->toIso8601String(),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.item_unit_id.0', 'Pilih unit fisik yang akan di-checkout.');

        $this->assertNull($borrowingItem->fresh()->borrow_date);
    }

    public function test_checkout_alat_with_unit_succeeds(): void
    {
        [$laboran, $borrowingItem, $item] = $this->approvedAlatItem();
        $unit = ItemUnit::factory()->for($item)->baik()->create();

        $this->withToken($this->tokenFor($laboran))
            ->patchJson("/api/borrowing-items/{$borrowingItem->id}/checkout", [
                'expected_return_date' => now()->addDays(3)->toIso8601String(),
                'item_unit_id' => $unit->id,
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.item_unit.id', $unit->id)
            ->assertJsonPath('data.item_unit.serial_number', $unit->serial_number);

        $fresh = $borrowingItem->fresh();
        $this->assertNotNull($fresh->borrow_date);
        $this->assertSame($unit->id, $fresh->item_unit_id);
        $this->assertSame($laboran->id, $fresh->checked_out_by);
    }

    public function test_checkout_rejects_unit_from_different_item(): void
    {
        [$laboran, $borrowingItem] = $this->approvedAlatItem();
        $otherItem = Item::factory()->alat()->create();
        $otherUnit = ItemUnit::factory()->for($otherItem)->baik()->create();

        $this->withToken($this->tokenFor($laboran))
            ->patchJson("/api/borrowing-items/{$borrowingItem->id}/checkout", [
                'expected_return_date' => now()->addDays(3)->toIso8601String(),
                'item_unit_id' => $otherUnit->id,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.item_unit_id.0', 'Unit yang dipilih bukan milik item ini.');

        $this->assertNull($borrowingItem->fresh()->borrow_date);
    }

    public function test_checkout_rejects_unit_already_borrowed(): void
    {
        [$laboran, $borrowingItem, $item] = $this->approvedAlatItem();
        $unit = ItemUnit::factory()->for($item)->baik()->create();

        $activeRequest = BorrowingRequest::factory()->diproses()->create();
        BorrowingItem::factory()->create([
            'borrowing_request_id' => $activeRequest->id,
            'item_id' => $item->id,
            'item_unit_id' => $unit->id,
            'quantity' => 1,
            'borrow_date' => now()->subDay(),
            'expected_return_date' => now()->addDays(5),
            'actual_return_date' => null,
        ]);

        $this->withToken($this->tokenFor($laboran))
            ->patchJson("/api/borrowing-items/{$borrowingItem->id}/checkout", [
                'expected_return_date' => now()->addDays(3)->toIso8601String(),
                'item_unit_id' => $unit->id,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.item_unit_id.0', 'Unit ini sedang dipinjam pada peminjaman lain.');

        $this->assertNull($borrowingItem->fresh()->borrow_date);
    }

    public function test_checkout_rejects_lost_or_deleted_unit(): void
    {
        [$laboran, $borrowingItem, $item] = $this->approvedAlatItem();
        $lostUnit = ItemUnit::factory()->for($item)->create(['condition' => 'hilang']);

        $this->withToken($this->tokenFor($laboran))
            ->patchJson("/api/borrowing-items/{$borrowingItem->id}/checkout", [
                'expected_return_date' => now()->addDays(3)->toIso8601String(),
                'item_unit_id' => $lostUnit->id,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.item_unit_id.0', 'Unit dengan status hilang atau dihapus tidak dapat di-checkout.');

        $this->assertNull($borrowingItem->fresh()->borrow_date);
    }

    public function test_checkout_bahan_without_unit_succeeds(): void
    {
        $laboran = User::factory()->laboran()->create();
        $peminjam = User::factory()->peminjam()->create();
        $item = Item::factory()->bahan()->create();
        $request = BorrowingRequest::factory()->disetujui()->create(['requested_by' => $peminjam->id]);
        $borrowingItem = BorrowingItem::factory()->create([
            'borrowing_request_id' => $request->id,
            'item_id' => $item->id,
            'item_unit_id' => null,
            'quantity' => 5,
            'borrow_date' => null,
            'expected_return_date' => null,
            'actual_return_date' => null,
        ]);

        $this->withToken($this->tokenFor($laboran))
            ->patchJson("/api/borrowing-items/{$borrowingItem->id}/checkout", [
                'expected_return_date' => now()->addDays(3)->toIso8601String(),
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.item_unit', null);

        $fresh = $borrowingItem->fresh();
        $this->assertNotNull($fresh->borrow_date);
        $this->assertNull($fresh->item_unit_id);
    }
}
