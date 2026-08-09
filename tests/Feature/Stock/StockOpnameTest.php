<?php

namespace Tests\Feature\Stock;

use App\Models\Item;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockOpnameTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    public function test_opname_increases_stock_with_in_adjustment(): void
    {
        $laboran = User::factory()->laboran()->create();
        $item = Item::factory()->bahan()->create(['stock_quantity' => 40]);

        $response = $this->withToken($this->tokenFor($laboran))
            ->postJson('/api/stock-opname', [
                'notes' => 'Opname naik',
                'items' => [
                    ['item_id' => $item->id, 'counted_quantity' => 45],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.summary.items_adjusted', 1)
            ->assertJsonPath('data.summary.items_unchanged', 0);

        $opnameId = $response->json('data.id');

        $this->assertDatabaseHas('stock_movements', [
            'item_id' => $item->id,
            'type' => 'in_adjustment',
            'quantity' => 5,
            'quantity_before' => 40,
            'quantity_after' => 45,
            'reference_type' => StockOpname::class,
            'reference_id' => $opnameId,
            'performed_by' => $laboran->id,
        ]);

        $this->assertSame(45.0, (float) $item->fresh()->stock_quantity);
    }

    public function test_opname_skips_when_counted_matches_system_stock(): void
    {
        $laboran = User::factory()->laboran()->create();
        $item = Item::factory()->bahan()->create(['stock_quantity' => 12]);

        $beforeCount = StockMovement::count();

        $this->withToken($this->tokenFor($laboran))
            ->postJson('/api/stock-opname', [
                'notes' => 'Tidak ada selisih',
                'items' => [
                    ['item_id' => $item->id, 'counted_quantity' => 12],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.summary.items_adjusted', 0)
            ->assertJsonPath('data.summary.items_unchanged', 1)
            ->assertJsonCount(0, 'data.movements');

        $this->assertSame($beforeCount, StockMovement::count());
        $this->assertSame(12.0, (float) $item->fresh()->stock_quantity);
        $this->assertDatabaseCount('stock_opnames', 1);
    }

    public function test_opname_processes_multiple_items_in_one_request(): void
    {
        $laboran = User::factory()->laboran()->create();
        $up = Item::factory()->bahan()->create(['stock_quantity' => 10]);
        $down = Item::factory()->bahan()->create(['stock_quantity' => 20]);
        $same = Item::factory()->bahan()->create(['stock_quantity' => 7]);

        $response = $this->withToken($this->tokenFor($laboran))
            ->postJson('/api/stock-opname', [
                'notes' => 'Opname rutin Januari 2026',
                'items' => [
                    ['item_id' => $up->id, 'counted_quantity' => 15],
                    ['item_id' => $down->id, 'counted_quantity' => 18],
                    ['item_id' => $same->id, 'counted_quantity' => 7],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.summary.items_submitted', 3)
            ->assertJsonPath('data.summary.items_adjusted', 2)
            ->assertJsonPath('data.summary.items_unchanged', 1)
            ->assertJsonCount(2, 'data.movements');

        $opnameId = $response->json('data.id');

        $this->assertDatabaseHas('stock_movements', [
            'item_id' => $up->id,
            'type' => 'in_adjustment',
            'quantity' => 5,
            'quantity_after' => 15,
            'reference_id' => $opnameId,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'item_id' => $down->id,
            'type' => 'out_adjustment',
            'quantity' => 2,
            'quantity_after' => 18,
            'reference_id' => $opnameId,
        ]);

        $this->assertDatabaseMissing('stock_movements', [
            'item_id' => $same->id,
            'reference_id' => $opnameId,
        ]);

        $this->assertSame(15.0, (float) $up->fresh()->stock_quantity);
        $this->assertSame(18.0, (float) $down->fresh()->stock_quantity);
        $this->assertSame(7.0, (float) $same->fresh()->stock_quantity);
    }

    public function test_peminjam_cannot_access_stock_opname(): void
    {
        $peminjam = User::factory()->peminjam()->create();
        $item = Item::factory()->bahan()->create(['stock_quantity' => 5]);

        $this->withToken($this->tokenFor($peminjam))
            ->postJson('/api/stock-opname', [
                'notes' => 'Coba opname',
                'items' => [
                    ['item_id' => $item->id, 'counted_quantity' => 6],
                ],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('stock_opnames', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame(5.0, (float) $item->fresh()->stock_quantity);
    }
}
