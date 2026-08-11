<?php

namespace Tests\Feature\Stock;

use App\Models\BorrowingItem;
use App\Models\BorrowingRequest;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Usage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Bukti aturan domain: bahan tidak boleh dipakai/dihabiskan melebihi stok.
 * Tidak ada max(0, ...) yang menyembunyikan transaksi tidak valid.
 */
class DomainStockValidationTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    public function test_usage_exceeding_bahan_stock_is_rejected(): void
    {
        // Skenario persis laporan: stok 50, ajukan pemakaian 150 -> harus 422.
        $user = User::factory()->peminjam()->create();
        $item = Item::factory()->bahan()->create(['stock_quantity' => 50]);

        $response = $this->withToken($this->tokenFor($user))
            ->postJson('/api/usages', [
                'item_id' => $item->id,
                'quantity_used' => 150,
                'purpose' => 'Pemakaian berlebih',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.quantity_used.0', fn ($msg) => str_contains($msg, 'Tersedia: 50'));

        $this->assertDatabaseMissing('usages', ['item_id' => $item->id]);
        $this->assertDatabaseMissing('stock_movements', [
            'item_id' => $item->id,
            'type' => 'out_usage',
        ]);
        // Stok tidak berubah sama sekali.
        $this->assertEquals(50, (float) $item->fresh()->stock_quantity);
    }

    public function test_usage_within_stock_succeeds(): void
    {
        $user = User::factory()->peminjam()->create();
        $item = Item::factory()->bahan()->create(['stock_quantity' => 50]);

        $this->withToken($this->tokenFor($user))
            ->postJson('/api/usages', [
                'item_id' => $item->id,
                'quantity_used' => 10,
                'purpose' => 'Pemakaian valid',
            ])
            ->assertCreated();

        $this->assertEquals(40, (float) $item->fresh()->stock_quantity);
    }

    public function test_stock_movement_outgoing_exceeding_stock_is_rejected(): void
    {
        $user = User::factory()->laboran()->create();
        $item = Item::factory()->bahan()->create(['stock_quantity' => 5]);

        $this->withToken($this->tokenFor($user))
            ->postJson('/api/stock-movements', [
                'item_id' => $item->id,
                'type' => 'out_usage',
                'quantity' => 10,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.quantity.0', fn ($msg) => str_contains($msg, 'Tersedia: 5'));

        $this->assertEquals(5, (float) $item->fresh()->stock_quantity);
    }

    public function test_bahan_borrowing_checkout_exceeding_stock_is_rejected(): void
    {
        $peminjam = User::factory()->peminjam()->create();
        $laboran = User::factory()->laboran()->create();
        $item = Item::factory()->bahan()->create(['stock_quantity' => 5]);

        $request = BorrowingRequest::factory()->disetujui()->create([
            'requested_by' => $peminjam->id,
        ]);

        $borrowingItem = BorrowingItem::factory()->create([
            'borrowing_request_id' => $request->id,
            'item_id' => $item->id,
            'item_unit_id' => null,
            'quantity' => 10, // > stok
            'borrow_date' => null,
            'expected_return_date' => null,
            'actual_return_date' => null,
        ]);

        $this->withToken($this->tokenFor($laboran))
            ->patchJson("/api/borrowing-items/{$borrowingItem->id}/checkout", [
                'expected_return_date' => now()->addDays(3)->toIso8601String(),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.quantity.0', fn ($msg) => str_contains($msg, 'Tersedia: 5'));

        // Tidak ada perubahan parsial: stok tetap dan unit belum di-checkout.
        $this->assertEquals(5, (float) $item->fresh()->stock_quantity);
        $this->assertNull($borrowingItem->fresh()->borrow_date);
        $this->assertDatabaseMissing('stock_movements', [
            'reference_id' => $borrowingItem->id,
            'type' => 'out_borrow',
        ]);
    }

    public function test_bahan_borrowing_must_not_use_item_unit_id(): void
    {
        $peminjam = User::factory()->peminjam()->create();
        $laboran = User::factory()->laboran()->create();
        $bahan = Item::factory()->bahan()->create(['stock_quantity' => 20]);
        $alat = Item::factory()->alat()->create();
        $unitMilikAlat = ItemUnit::factory()->for($alat)->baik()->create();

        $request = BorrowingRequest::factory()->disetujui()->create([
            'requested_by' => $peminjam->id,
        ]);

        $borrowingItem = BorrowingItem::factory()->create([
            'borrowing_request_id' => $request->id,
            'item_id' => $bahan->id,
            'item_unit_id' => null,
            'quantity' => 2,
            'borrow_date' => null,
            'expected_return_date' => null,
            'actual_return_date' => null,
        ]);

        $this->withToken($this->tokenFor($laboran))
            ->patchJson("/api/borrowing-items/{$borrowingItem->id}/checkout", [
                'expected_return_date' => now()->addDays(3)->toIso8601String(),
                'item_unit_id' => $unitMilikAlat->id,
            ])
            ->assertUnprocessable();

        $this->assertNull($borrowingItem->fresh()->borrow_date);
    }

    public function test_no_negative_stock_when_two_large_requests_run_sequentially(): void
    {
        $user = User::factory()->peminjam()->create();
        $item = Item::factory()->bahan()->create(['stock_quantity' => 5]);

        // Request A memakai 4 -> stok jadi 1.
        $this->withToken($this->tokenFor($user))
            ->postJson('/api/usages', [
                'item_id' => $item->id,
                'quantity_used' => 4,
                'purpose' => 'A',
            ])
            ->assertCreated();

        $this->assertEquals(1, (float) $item->fresh()->stock_quantity);

        // Request B memakai 4 -> 1 < 4, harus ditolak, stok tetap 1.
        $this->withToken($this->tokenFor($user))
            ->postJson('/api/usages', [
                'item_id' => $item->id,
                'quantity_used' => 4,
                'purpose' => 'B',
            ])
            ->assertUnprocessable();

        $this->assertEquals(1, (float) $item->fresh()->stock_quantity);
        $this->assertGreaterThanOrEqual(0, (float) $item->fresh()->stock_quantity);
    }
}
