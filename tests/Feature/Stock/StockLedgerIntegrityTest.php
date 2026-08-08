<?php

namespace Tests\Feature\Stock;

use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Usage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockLedgerIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    public function test_posting_usage_creates_out_usage_stock_movement(): void
    {
        $user = User::factory()->staf()->create();
        $item = Item::factory()->bahan()->create(['stock_quantity' => 100]);

        $response = $this->withToken($this->tokenFor($user))
            ->postJson('/api/usages', [
                'item_id' => $item->id,
                'quantity_used' => 12.5,
                'purpose' => 'Pemakaian uji ledger',
            ])
            ->assertCreated();

        $usageId = $response->json('data.id') ?? $response->json('id');

        $this->assertDatabaseHas('stock_movements', [
            'item_id' => $item->id,
            'type' => 'out_usage',
            'reference_type' => Usage::class,
            'reference_id' => $usageId,
            'quantity' => 12.5,
        ]);
    }

    public function test_usage_stock_matches_movement_quantity_after(): void
    {
        $user = User::factory()->staf()->create();
        $item = Item::factory()->bahan()->create(['stock_quantity' => 80]);

        $this->withToken($this->tokenFor($user))
            ->postJson('/api/usages', [
                'item_id' => $item->id,
                'quantity_used' => 15,
                'purpose' => 'Cek sinkron stok',
            ])
            ->assertCreated();

        $movement = StockMovement::where('item_id', $item->id)
            ->where('type', 'out_usage')
            ->latest('id')
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals(
            (float) $movement->quantity_after,
            (float) $item->fresh()->stock_quantity
        );
    }

    public function test_mixed_stock_movements_reconcile_to_sum_of_ledger(): void
    {
        $user = User::factory()->admin()->create();
        $token = $this->tokenFor($user);
        $item = Item::factory()->bahan()->create(['stock_quantity' => 0]);

        $payloads = [
            ['type' => 'in_purchase', 'quantity' => 50],
            ['type' => 'in_purchase', 'quantity' => 30],
            ['type' => 'out_usage', 'quantity' => 20],
            ['type' => 'in_return', 'quantity' => 5],
            ['type' => 'out_disposal', 'quantity' => 3],
            ['type' => 'in_adjustment', 'quantity' => 10],
            ['type' => 'out_adjustment', 'quantity' => 4],
        ];

        foreach ($payloads as $payload) {
            $this->withToken($token)
                ->postJson('/api/stock-movements', [
                    'item_id' => $item->id,
                    'type' => $payload['type'],
                    'quantity' => $payload['quantity'],
                ])
                ->assertCreated();
        }

        $incomingTypes = ['in_purchase', 'in_return', 'in_adjustment', 'transfer_in'];
        $outgoingTypes = ['out_borrow', 'out_usage', 'out_disposal', 'out_adjustment', 'transfer_out'];

        $totalIn = (float) StockMovement::where('item_id', $item->id)
            ->whereIn('type', $incomingTypes)
            ->sum('quantity');

        $totalOut = (float) StockMovement::where('item_id', $item->id)
            ->whereIn('type', $outgoingTypes)
            ->sum('quantity');

        $expectedStock = $totalIn - $totalOut;

        $this->assertEquals(
            $expectedStock,
            (float) $item->fresh()->stock_quantity
        );
    }

    public function test_patch_cannot_change_stock_movement_quantity(): void
    {
        $user = User::factory()->admin()->create();
        $token = $this->tokenFor($user);
        $item = Item::factory()->bahan()->create(['stock_quantity' => 0]);

        $create = $this->withToken($token)
            ->postJson('/api/stock-movements', [
                'item_id' => $item->id,
                'type' => 'in_purchase',
                'quantity' => 25,
                'notes' => 'awal',
            ])
            ->assertCreated();

        $movementId = $create->json('data.id') ?? $create->json('id');
        $originalQuantity = (float) StockMovement::findOrFail($movementId)->quantity;

        $this->withToken($token)
            ->patchJson("/api/stock-movements/{$movementId}", [
                'quantity' => 999,
                'notes' => 'coba ubah kuantitas',
            ])
            ->assertUnprocessable();

        $this->assertSame(
            $originalQuantity,
            (float) StockMovement::findOrFail($movementId)->quantity
        );
    }
}
