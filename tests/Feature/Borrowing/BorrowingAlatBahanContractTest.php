<?php

namespace Tests\Feature\Borrowing;

use App\Models\BorrowingItem;
use App\Models\BorrowingRequest;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 5: tegaskan contract borrowing alat vs bahan.
 *
 * Alat : request -> item_id, unit fisik (ItemUnit) baru ditentukan saat checkout.
 * Bahan: request -> item_id + quantity, TANPA ItemUnit. Stock turun saat checkout,
 *        dan hanya jika stock aktual mencukupi.
 */
class BorrowingAlatBahanContractTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    private function approvedRequest(User $peminjam, Item $item, int $quantity = 1): BorrowingItem
    {
        $laboran = User::factory()->laboran()->create();
        $request = BorrowingRequest::factory()->disetujui()->create(['requested_by' => $peminjam->id]);

        return BorrowingItem::factory()->create([
            'borrowing_request_id' => $request->id,
            'item_id' => $item->id,
            'item_unit_id' => null,
            'quantity' => $quantity,
            'borrow_date' => null,
            'expected_return_date' => null,
            'actual_return_date' => null,
        ]);
    }

    private function checkoutBody(ItemUnit $unit = null, string $type = 'alat'): array
    {
        $body = ['expected_return_date' => now()->addDays(3)->toIso8601String()];
        if ($type === 'alat' && $unit) {
            $body['item_unit_id'] = $unit->id;
        }

        return $body;
    }

    public function test_alat_checkout_with_valid_unit_succeeds(): void
    {
        $laboran = User::factory()->laboran()->create();
        $peminjam = User::factory()->peminjam()->create();
        $item = Item::factory()->alat()->create();
        $unit = ItemUnit::factory()->for($item)->baik()->create();
        $bi = $this->approvedRequest($peminjam, $item);

        $this->withToken($this->tokenFor($laboran))
            ->patchJson("/api/borrowing-items/{$bi->id}/checkout", $this->checkoutBody($unit, 'alat'))
            ->assertSuccessful()
            ->assertJsonPath('data.item_unit.id', $unit->id);

        $this->assertNotNull($bi->fresh()->borrow_date);
        $this->assertSame($unit->id, $bi->fresh()->item_unit_id);
    }

    public function test_alat_checkout_with_unit_from_other_item_fails(): void
    {
        $laboran = User::factory()->laboran()->create();
        $peminjam = User::factory()->peminjam()->create();
        $item = Item::factory()->alat()->create();
        $otherItem = Item::factory()->alat()->create();
        $otherUnit = ItemUnit::factory()->for($otherItem)->baik()->create();
        $bi = $this->approvedRequest($peminjam, $item);

        $this->withToken($this->tokenFor($laboran))
            ->patchJson("/api/borrowing-items/{$bi->id}/checkout", $this->checkoutBody($otherUnit, 'alat'))
            ->assertUnprocessable()
            ->assertJsonPath('errors.item_unit_id.0', 'Unit yang dipilih bukan milik item ini.');

        $this->assertNull($bi->fresh()->borrow_date);
    }

    public function test_bahan_checkout_with_item_unit_fails(): void
    {
        $laboran = User::factory()->laboran()->create();
        $peminjam = User::factory()->peminjam()->create();
        $item = Item::factory()->bahan()->create(['stock_quantity' => 20]);
        $alat = Item::factory()->alat()->create();
        $unitMilikAlat = ItemUnit::factory()->for($alat)->baik()->create();
        $bi = $this->approvedRequest($peminjam, $item, 2);

        $this->withToken($this->tokenFor($laboran))
            ->patchJson("/api/borrowing-items/{$bi->id}/checkout", [
                'expected_return_date' => now()->addDays(3)->toIso8601String(),
                'item_unit_id' => $unitMilikAlat->id,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.item_unit_id.0', 'Item bahan tidak menggunakan unit fisik.');

        $this->assertNull($bi->fresh()->borrow_date);
        $this->assertNull($bi->fresh()->item_unit_id);
    }

    public function test_bahan_checkout_with_valid_quantity_succeeds(): void
    {
        $laboran = User::factory()->laboran()->create();
        $peminjam = User::factory()->peminjam()->create();
        $item = Item::factory()->bahan()->create(['stock_quantity' => 20]);
        $bi = $this->approvedRequest($peminjam, $item, 5);

        $this->withToken($this->tokenFor($laboran))
            ->patchJson("/api/borrowing-items/{$bi->id}/checkout", [
                'expected_return_date' => now()->addDays(3)->toIso8601String(),
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.item_unit', null);

        $this->assertSame(15.0, (float) $item->fresh()->stock_quantity);
    }

    public function test_bahan_checkout_exceeding_stock_fails(): void
    {
        $laboran = User::factory()->laboran()->create();
        $peminjam = User::factory()->peminjam()->create();
        $item = Item::factory()->bahan()->create(['stock_quantity' => 5]);
        $bi = $this->approvedRequest($peminjam, $item, 10);

        $this->withToken($this->tokenFor($laboran))
            ->patchJson("/api/borrowing-items/{$bi->id}/checkout", [
                'expected_return_date' => now()->addDays(3)->toIso8601String(),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.quantity.0', fn ($m) => str_contains($m, 'Tersedia: 5'));

        $this->assertSame(5.0, (float) $item->fresh()->stock_quantity);
        $this->assertNull($bi->fresh()->borrow_date);
        $this->assertDatabaseMissing('stock_movements', [
            'reference_id' => $bi->id,
            'type' => 'out_borrow',
        ]);
    }

    public function test_checkout_alat_changes_unit_availability_not_item_stock(): void
    {
        $laboran = User::factory()->laboran()->create();
        $peminjam = User::factory()->peminjam()->create();
        $item = Item::factory()->alat()->create();
        $unit = ItemUnit::factory()->for($item)->baik()->create();
        $bi = $this->approvedRequest($peminjam, $item);

        // Sebelum checkout: unit tersedia (is_borrowed = false), item alat tidak punya stock.
        $before = $this->withToken($this->tokenFor($laboran))
            ->getJson("/api/items/{$item->id}/units?per_page=50")
            ->json('data');
        $beforeUnit = collect($before)->firstWhere('id', $unit->id);
        $this->assertFalse((bool) $beforeUnit['is_borrowed']);
        $this->assertNull($item->fresh()->stock_quantity);

        $this->withToken($this->tokenFor($laboran))
            ->patchJson("/api/borrowing-items/{$bi->id}/checkout", $this->checkoutBody($unit, 'alat'))
            ->assertSuccessful();

        // Sesudah checkout: unit jadi dipinjam (is_borrowed = true).
        $after = $this->withToken($this->tokenFor($laboran))
            ->getJson("/api/items/{$item->id}/units?per_page=50")
            ->json('data');
        $afterUnit = collect($after)->firstWhere('id', $unit->id);
        $this->assertTrue((bool) $afterUnit['is_borrowed']);

        // Item alat tetap tidak memiliki stock_quantity (tidak berubah jadi angka).
        $this->assertNull($item->fresh()->stock_quantity);
    }

    public function test_create_request_rejects_item_unit_id(): void
    {
        $peminjam = User::factory()->peminjam()->create();
        $item = Item::factory()->bahan()->create(['stock_quantity' => 20]);
        $alat = Item::factory()->alat()->create();
        $unit = ItemUnit::factory()->for($alat)->baik()->create();

        $this->withToken($this->tokenFor($peminjam))
            ->postJson('/api/borrowing-requests', [
                'requested_by' => $peminjam->id,
                'purpose' => 'Praktikum',
                'items' => [
                    ['item_id' => $item->id, 'quantity' => 2, 'item_unit_id' => $unit->id],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.item_unit_id']);
    }

    public function test_create_request_alat_requires_quantity_one(): void
    {
        $peminjam = User::factory()->peminjam()->create();
        $item = Item::factory()->alat()->create();

        $this->withToken($this->tokenFor($peminjam))
            ->postJson('/api/borrowing-requests', [
                'requested_by' => $peminjam->id,
                'purpose' => 'Praktikum',
                'items' => [
                    ['item_id' => $item->id, 'quantity' => 3],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items.0.quantity' => 'Alat dipinjam per unit; quantity harus tepat 1.',
            ]);
    }
}
