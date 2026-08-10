<?php

namespace Tests\Feature\Audit;

use App\Models\AuditTrail;
use App\Models\BorrowingItem;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemCalibration;
use App\Models\ItemMaintenance;
use App\Models\ItemUnit;
use App\Models\StockMovement;
use App\Models\Usage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Verifies GET /items/{id}/audit-trails aggregates every audit relevant to an item:
 * direct Item edits, BorrowingItem, Usage, StockMovement, ItemCalibration and
 * ItemMaintenance records (via the item's physical units).
 */
class ItemAuditTrailAggregationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(User $user): static
    {
        Auth::forgetGuards();

        return $this->actingAs($user, 'sanctum');
    }

    private function auditTrailFor(Item $item): array
    {
        $response = $this->getJson("/api/items/{$item->id}/audit-trails?per_page=100")
            ->assertOk()
            ->json();

        return $response['data'] ?? [];
    }

    public function test_item_created_audit_appears_on_item_audit_trail(): void
    {
        $admin = User::factory()->laboran()->create();
        $categoryId = Category::factory()->alat()->create()->id;

        $itemId = $this->actingAsUser($admin)
            ->postJson('/api/items', [
                'category_id' => $categoryId,
                'code' => 'ITM-AUD-001',
                'name' => 'Mikroskop Binokuler',
                'unit' => 'unit',
                'stock_quantity' => 0,
                'minimum_stock' => 0,
                'created_by' => $admin->id,
            ])
            ->assertCreated()
            ->json('data.id');

        $item = Item::findOrFail($itemId);

        $trail = $this->auditTrailFor($item);

        $this->assertNotEmpty($trail);

        $createdAudit = collect($trail)->first(
            fn (array $a) => $a['auditable_type'] === Item::class
                && (int) $a['auditable_id'] === $item->id
                && $a['action'] === 'created'
        );

        $this->assertNotNull($createdAudit, 'Item created audit not found in item audit trail.');
        $this->assertSame($admin->id, (int) $createdAudit['user_id']);
    }

    public function test_full_borrowing_cycle_audits_all_appear_on_item_audit_trail(): void
    {
        $admin = User::factory()->laboran()->create();
        $staff = User::factory()->peminjam()->create();
        $item = Item::factory()->alat()->create();
        $unit = ItemUnit::factory()->for($item)->baik()->create();

        // ajukan (submitted via HTTP by staff)
        $borrowingItemId = $this->actingAsUser($staff)
            ->postJson('/api/borrowing-requests', [
                'requested_by' => $staff->id,
                'purpose' => 'Praktikum kimia analitik',
                'items' => [
                    ['item_id' => $item->id, 'quantity' => 1],
                ],
            ])
            ->assertCreated()
            ->json('data.items.0.id');

        $borrowingItem = BorrowingItem::findOrFail($borrowingItemId);

        // Assign the physical unit (the HTTP store does not set it; the laboran assigns it)
        $this->actingAsUser($admin);
        $borrowingItem->update(['item_unit_id' => $unit->id]);

        // approve -> checkout -> return (damaged)
        $this->actingAsUser($admin)
            ->patchJson("/api/borrowing-requests/{$borrowingItem->borrowing_request_id}/approve")
            ->assertSuccessful();

        $this->actingAsUser($admin)
            ->patchJson("/api/borrowing-items/{$borrowingItem->id}/checkout", [
                'expected_return_date' => now()->addDays(3)->toIso8601String(),
            ])
            ->assertSuccessful();

        $this->actingAsUser($admin)
            ->patchJson("/api/borrowing-items/{$borrowingItem->id}/return", [
                'condition_after' => 'rusak_berat',
                'is_damaged' => true,
                'damage_notes' => 'Housing retak saat praktikum.',
                'check_notes' => 'Perlu perbaikan.',
            ])
            ->assertSuccessful();

        $trail = $this->auditTrailFor($item);

        $borrowingItemAudits = collect($trail)->filter(
            fn (array $a) => $a['auditable_type'] === BorrowingItem::class
                && (int) $a['auditable_id'] === $borrowingItem->id
        );

        $this->assertGreaterThanOrEqual(
            3,
            $borrowingItemAudits->count(),
            'Expected the full cycle to produce multiple BorrowingItem audits, got '.$borrowingItemAudits->count().'.'
        );
        $this->assertTrue(
            $borrowingItemAudits->contains('action', 'created'),
            'BorrowingItem created audit missing from the item audit trail.'
        );
        $this->assertTrue(
            $borrowingItemAudits->contains('action', 'updated'),
            'BorrowingItem updated audit (checkout/return) missing from the item audit trail.'
        );

        $maintenanceAudit = collect($trail)->first(
            fn (array $a) => $a['auditable_type'] === ItemMaintenance::class && $a['action'] === 'created'
        );
        $this->assertNotNull(
            $maintenanceAudit,
            'ItemMaintenance created audit (auto-generated from damaged return) missing from the item audit trail.'
        );
    }

    public function test_usage_and_stock_movement_audits_appear_on_bahan_item_audit_trail(): void
    {
        $admin = User::factory()->laboran()->create();
        $staff = User::factory()->peminjam()->create();
        $bahan = Item::factory()->bahan()->create(['stock_quantity' => 100]);

        $usageId = $this->actingAsUser($staff)
            ->postJson('/api/usages', [
                'item_id' => $bahan->id,
                'quantity_used' => 5,
                'purpose' => 'Pemakaian reagen untuk percobaan',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAsUser($admin)
            ->patchJson("/api/usages/{$usageId}/verify")
            ->assertSuccessful();

        $trail = $this->auditTrailFor($bahan);

        $usageAudits = collect($trail)->filter(
            fn (array $a) => $a['auditable_type'] === Usage::class && (int) $a['auditable_id'] === $usageId
        );

        $this->assertTrue($usageAudits->contains('action', 'created'), 'Usage created audit missing.');
        $this->assertTrue($usageAudits->contains('action', 'updated'), 'Usage verified (updated) audit missing.');

        $stockMovementAudit = collect($trail)->first(
            fn (array $a) => $a['auditable_type'] === StockMovement::class && $a['action'] === 'created'
        );
        $this->assertNotNull($stockMovementAudit, 'StockMovement created audit missing from the item audit trail.');
    }

    public function test_calibration_and_maintenance_audits_appear_for_alat_item(): void
    {
        $admin = User::factory()->laboran()->create();
        $item = Item::factory()->alat()->create();
        $unit = ItemUnit::factory()->for($item)->baik()->create();

        $calibration = $this->actingAsUser($admin)
            ->postJson('/api/calibrations', [
                'item_unit_id' => $unit->id,
                'calibration_date' => now()->toDateString(),
                'next_calibration_date' => now()->addMonths(6)->toDateString(),
                'calibrated_by' => 'Teknisi Lab',
                'certificate_number' => 'CAL-2026-001',
                'result' => 'lulus',
            ])
            ->assertCreated()
            ->json('data.id');

        $maintenance = $this->actingAsUser($admin)
            ->postJson('/api/maintenances', [
                'item_unit_id' => $unit->id,
                'maintenance_date' => now()->toDateString(),
                'description' => 'Servis berkala',
                'performed_by' => 'Teknisi Lab',
                'status' => 'proses',
            ])
            ->assertCreated()
            ->json('data.id');

        $trail = $this->auditTrailFor($item);

        $calibrationAudit = collect($trail)->first(
            fn (array $a) => $a['auditable_type'] === ItemCalibration::class
                && (int) $a['auditable_id'] === (int) $calibration
        );
        $this->assertNotNull($calibrationAudit, 'ItemCalibration created audit missing from the item audit trail.');

        $maintenanceAudit = collect($trail)->first(
            fn (array $a) => $a['auditable_type'] === ItemMaintenance::class
                && (int) $a['auditable_id'] === (int) $maintenance
        );
        $this->assertNotNull($maintenanceAudit, 'ItemMaintenance created audit missing from the item audit trail.');
    }

    public function test_audit_trails_are_isolated_per_item(): void
    {
        $admin = User::factory()->laboran()->create();
        $staff = User::factory()->peminjam()->create();
        $borrowedItem = Item::factory()->alat()->create();
        $unit = ItemUnit::factory()->for($borrowedItem)->baik()->create();
        $unrelatedItem = Item::factory()->alat()->create();

        $borrowingItemId = $this->actingAsUser($staff)
            ->postJson('/api/borrowing-requests', [
                'requested_by' => $staff->id,
                'purpose' => 'Siklus hanya untuk item pertama',
                'items' => [
                    ['item_id' => $borrowedItem->id, 'quantity' => 1],
                ],
            ])
            ->assertCreated()
            ->json('data.items.0.id');

        BorrowingItem::find($borrowingItemId)->update(['item_unit_id' => $unit->id]);

        $requestId = BorrowingItem::find($borrowingItemId)->borrowing_request_id;

        $this->actingAsUser($admin)
            ->patchJson("/api/borrowing-requests/{$requestId}/approve")
            ->assertSuccessful();

        $this->actingAsUser($admin)
            ->patchJson("/api/borrowing-items/{$borrowingItemId}/checkout", [
                'expected_return_date' => now()->addDays(3)->toIso8601String(),
            ])
            ->assertSuccessful();

        $this->actingAsUser($admin)
            ->patchJson("/api/borrowing-items/{$borrowingItemId}/return", [
                'condition_after' => 'baik',
                'is_damaged' => false,
            ])
            ->assertSuccessful();

        $borrowedTrail = $this->auditTrailFor($borrowedItem);
        $this->assertTrue(
            collect($borrowedTrail)->contains(
                fn (array $a) => $a['auditable_type'] === BorrowingItem::class
                    && (int) $a['auditable_id'] === (int) $borrowingItemId
            ),
            'Borrowed item trail should contain its own BorrowingItem audits.'
        );

        $unrelatedTrail = $this->auditTrailFor($unrelatedItem);
        $this->assertTrue(
            collect($unrelatedTrail)->contains(
                fn (array $a) => $a['auditable_type'] === Item::class
                    && (int) $a['auditable_id'] === $unrelatedItem->id
            ),
            'Unrelated item trail should still show its own creation audit.'
        );
        $this->assertFalse(
            collect($unrelatedTrail)->contains(
                fn (array $a) => $a['auditable_type'] === BorrowingItem::class
                    && (int) $a['auditable_id'] === (int) $borrowingItemId
            ),
            'Unrelated item audit trail leaked another item\'s BorrowingItem audits.'
        );
        $this->assertFalse(
            collect($unrelatedTrail)->contains(fn (array $a) => $a['auditable_type'] === Item::class && (int) $a['auditable_id'] !== $unrelatedItem->id),
            'Unrelated item audit trail leaked audits belonging to another item.'
        );
    }

    public function test_audit_trail_is_paginated_and_ordered_by_created_at_desc(): void
    {
        $admin = User::factory()->laboran()->create();
        $item = Item::factory()->alat()->create();

        $this->actingAsUser($admin);

        $insertAudit = function (string $type, int $id, string $action, Carbon $when): void {
            $audit = new AuditTrail([
                'auditable_type' => $type,
                'auditable_id' => $id,
                'action' => $action,
                'new_values' => [],
            ]);
            $audit->created_at = $when;
            $audit->save();
        };

        $insertAudit(Item::class, $item->id, 'updated', now()->subHours(3));
        $insertAudit(Item::class, $item->id, 'updated', now()->subHours(2));
        $insertAudit(Item::class, $item->id, 'updated', now()->subHours(1));

        $payload = $this->getJson("/api/items/{$item->id}/audit-trails?per_page=2")
            ->assertOk()
            ->json();

        $this->assertArrayHasKey('data', $payload);
        $this->assertArrayHasKey('meta', $payload);
        $this->assertCount(2, $payload['data']);
        $this->assertSame(4, $payload['meta']['total']);
        $this->assertSame(2, $payload['meta']['per_page']);
        $this->assertSame(2, $payload['meta']['last_page']);

        $timestamps = array_column($payload['data'], 'created_at');
        for ($i = 1; $i < count($timestamps); $i++) {
            $this->assertTrue(
                $timestamps[$i - 1] > $timestamps[$i],
                'Audit trail is not ordered by created_at desc.'
            );
        }
    }
}
